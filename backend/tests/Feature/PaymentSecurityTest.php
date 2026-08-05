<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The payment path, from the attacker's point of view.
 *
 * The original webhook re-verified whatever transaction id the caller supplied
 * and granted a plan whenever that came back successful, never checking that
 * the transaction belonged to the payment being settled. One genuine 2 900 F
 * payment, replayed, bought a subscription forever. These tests pin the
 * conditions that closed it.
 */
class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_HASH = 'test-webhook-hash';

    /**
     * What the fake gateway reports for a given tx_ref.
     *
     * One stub, registered once, reading this map; stacking Http::fake() calls
     * would not work here, because the earliest matching stub wins and later
     * ones are silently ignored.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $transactions = [];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        Http::fake(function ($request) {
            if (! str_contains($request->url(), 'verify_by_reference')) {
                return Http::response([
                    'status' => 'success',
                    'data'   => ['link' => 'https://checkout.flutterwave.test/abc'],
                ]);
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return Http::response([
                'status' => 'success',
                'data'   => $this->transactions[$query['tx_ref'] ?? ''] ?? null,
            ]);
        });
    }

    /** @param array<string, mixed> $transaction */
    private function gatewayReports(string $txRef, array $transaction): void
    {
        $this->transactions[$txRef] = $transaction;
    }

    public function test_a_webhook_without_a_signature_is_rejected(): void
    {
        $payment = $this->pendingPayment();

        $this->postJson('/api/webhooks/flutterwave', [
            'data' => ['tx_ref' => $payment->tx_ref, 'status' => 'successful', 'id' => 1],
        ])->assertStatus(401);

        $this->assertPlanUnchanged($payment);
    }

    public function test_a_webhook_with_a_wrong_signature_is_rejected(): void
    {
        $payment = $this->pendingPayment();

        $this->withHeader('verif-hash', 'not-the-secret')
            ->postJson('/api/webhooks/flutterwave', [
                'data' => ['tx_ref' => $payment->tx_ref, 'status' => 'successful', 'id' => 1],
            ])->assertStatus(401);

        $this->assertPlanUnchanged($payment);
    }

    /**
     * Fail closed. Previously an unset FLW_WEBHOOK_HASH skipped the check
     * altogether, so a misconfigured deployment trusted anyone who found the URL.
     */
    public function test_an_unconfigured_secret_rejects_every_webhook(): void
    {
        config(['services.flutterwave.webhook_hash' => null]);

        $payment = $this->pendingPayment();

        $this->withHeader('verif-hash', 'anything')
            ->postJson('/api/webhooks/flutterwave', [
                'data' => ['tx_ref' => $payment->tx_ref, 'status' => 'successful', 'id' => 1],
            ])->assertStatus(401);

        $this->assertPlanUnchanged($payment);
    }

    /**
     * The core of the replay fix: settlement looks the transaction up by *our*
     * reference, so a real transaction belonging to another payment cannot be
     * pointed at this one.
     */
    public function test_a_transaction_for_another_reference_never_activates_a_plan(): void
    {
        $payment = $this->pendingPayment();

        $this->gatewayReports($payment->tx_ref, [
            'tx_ref'   => 'RSV-SOMEONE-ELSE',
            'status'   => 'successful',
            'amount'   => 2900,
            'currency' => 'XAF',
            'id'       => 99,
        ]);

        $this->signedWebhook($payment)->assertOk();

        $this->assertPlanUnchanged($payment);
    }

    public function test_the_same_gateway_transaction_cannot_settle_two_payments(): void
    {
        $user = User::factory()->create();

        $first  = $this->pendingPayment($user);
        $second = $this->pendingPayment($user);

        // A genuine settlement.
        $this->gatewayReports($first->tx_ref, [
            'tx_ref' => $first->tx_ref, 'status' => 'successful',
            'amount' => 2900, 'currency' => 'XAF', 'id' => 4242,
        ]);
        $this->signedWebhook($first)->assertOk();

        $this->assertSame(Payment::STATUS_SUCCESSFUL, $first->fresh()->status);
        $firstExpiry = $user->fresh()->plan_expires_at;

        // Replay: same gateway transaction, aimed at the second payment.
        $this->gatewayReports($second->tx_ref, [
            'tx_ref' => $second->tx_ref, 'status' => 'successful',
            'amount' => 2900, 'currency' => 'XAF', 'id' => 4242,
        ]);
        $this->signedWebhook($second)->assertOk();

        $this->assertSame(Payment::STATUS_PENDING, $second->fresh()->status);
        $this->assertTrue($firstExpiry->equalTo($user->fresh()->plan_expires_at));
    }

    public function test_an_underpaid_transaction_does_not_activate_a_plan(): void
    {
        $payment = $this->pendingPayment();

        $this->gatewayReports($payment->tx_ref, [
            'tx_ref' => $payment->tx_ref, 'status' => 'successful',
            'amount' => 100, 'currency' => 'XAF', 'id' => 7,
        ]);

        $this->signedWebhook($payment)->assertOk();

        $this->assertPlanUnchanged($payment);
    }

    public function test_a_transaction_in_another_currency_does_not_activate_a_plan(): void
    {
        $payment = $this->pendingPayment();

        $this->gatewayReports($payment->tx_ref, [
            'tx_ref' => $payment->tx_ref, 'status' => 'successful',
            'amount' => 2900, 'currency' => 'NGN', 'id' => 8,
        ]);

        $this->signedWebhook($payment)->assertOk();

        $this->assertPlanUnchanged($payment);
    }

    public function test_a_valid_webhook_activates_the_plan_once(): void
    {
        $user    = User::factory()->create();
        $payment = $this->pendingPayment($user);

        $this->gatewayReports($payment->tx_ref, [
            'tx_ref' => $payment->tx_ref, 'status' => 'successful',
            'amount' => 2900, 'currency' => 'XAF', 'id' => 555,
            'flw_ref' => 'FLW-REF-555', 'payment_type' => 'mobilemoneycm',
        ]);

        $this->signedWebhook($payment)->assertOk();
        // Flutterwave retries; a second delivery must be a no-op.
        $this->signedWebhook($payment)->assertOk();

        $user->refresh();
        $this->assertSame(User::PLAN_PRO, $user->plan);
        $this->assertTrue($user->plan_expires_at->between(now()->addDays(27), now()->addDays(32)));
        $this->assertSame('mobilemoneycm', $payment->fresh()->payment_method);
    }

    public function test_a_merchant_cannot_verify_someone_elses_payment(): void
    {
        $payment  = $this->pendingPayment();
        $attacker = User::factory()->create();

        $this->actingAs($attacker)
            ->postJson('/api/payments/verify', ['tx_ref' => $payment->tx_ref])
            ->assertNotFound();
    }

    public function test_the_amount_comes_from_our_price_list_not_the_request(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/payments/initiate', [
            'plan'          => User::PLAN_PRO,
            'billing_cycle' => Payment::CYCLE_MONTHLY,
            'amount'        => 1, // Ignored: we decide what a plan costs.
        ])->assertOk();

        $this->assertSame(2900, Payment::firstOrFail()->amount);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function pendingPayment(?User $user = null): Payment
    {
        return Payment::create([
            'user_id'       => ($user ?? User::factory()->create())->id,
            'tx_ref'        => Payment::generateTxRef(),
            'plan'          => User::PLAN_PRO,
            'billing_cycle' => Payment::CYCLE_MONTHLY,
            'amount'        => 2900,
            'currency'      => 'XAF',
            'status'        => Payment::STATUS_PENDING,
        ]);
    }

    private function signedWebhook(Payment $payment)
    {
        return $this->withHeader('verif-hash', self::WEBHOOK_HASH)
            ->postJson('/api/webhooks/flutterwave', [
                'data' => ['tx_ref' => $payment->tx_ref, 'status' => 'successful'],
            ]);
    }

    private function assertPlanUnchanged(Payment $payment): void
    {
        $this->assertNotSame(Payment::STATUS_SUCCESSFUL, $payment->fresh()->status);
        $this->assertSame(User::PLAN_FREE, $payment->user->fresh()->effectivePlan());
    }
}
