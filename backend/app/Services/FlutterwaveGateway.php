<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Everything that talks to Flutterwave, in one place.
 *
 * Isolating the HTTP calls keeps SubscriptionService free of transport concerns
 * and makes the gateway trivially fakeable in tests — which matters, because
 * these are the code paths where money and entitlements meet.
 */
class FlutterwaveGateway
{
    private const BASE_URL = 'https://api.flutterwave.com/v3';

    /** Payment methods that make sense across our markets. */
    private const PAYMENT_OPTIONS = 'mobilemoneycm,mobilemoneyghana,mobilemoneyuganda,mobilemoneyrwanda,card';

    public function isConfigured(): bool
    {
        return filled(config('services.flutterwave.secret_key'));
    }

    /**
     * Open a hosted checkout session.
     *
     * @return string|null The checkout URL, or null when the gateway refused.
     */
    public function createCheckout(Payment $payment, User $user): ?string
    {
        $response = $this->client()->post(self::BASE_URL . '/payments', [
            'tx_ref'       => $payment->tx_ref,
            'amount'       => $payment->amount,
            'currency'     => $payment->currency,
            // tx_ref travels in the URL so the SPA can verify the exact payment
            // it started, rather than guessing from the most recent one.
            'redirect_url' => config('app.frontend_url') . '/dashboard/billing?' . http_build_query([
                'flow'   => 'checkout',
                'tx_ref' => $payment->tx_ref,
            ]),
            'customer' => [
                'email'       => $user->email,
                'name'        => $user->name,
                'phonenumber' => $user->phone ?? '',
            ],
            'customizations' => [
                'title'       => 'Réserva ' . Payment::labelFor($payment->plan),
                'description' => 'Abonnement ' . Payment::labelFor($payment->plan) . ' — ' . $payment->cycleLabel(),
                'logo'        => config('app.frontend_url') . '/icons/icon-192.png',
            ],
            'payment_options' => self::PAYMENT_OPTIONS,
            'meta'            => [
                'payment_id' => $payment->id,
                'user_id'    => $user->id,
            ],
        ]);

        if (! $response->successful() || $response->json('status') !== 'success') {
            Log::error('Flutterwave checkout could not be created', [
                'payment'  => $payment->tx_ref,
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);

            return null;
        }

        return $response->json('data.link');
    }

    /**
     * Ask Flutterwave about a transaction, by our own reference.
     *
     * Looking it up by tx_ref rather than by a caller-supplied transaction id is
     * the point: the answer is then guaranteed to describe *this* payment.
     *
     * @return array<string, mixed>|null
     */
    public function findByReference(string $txRef): ?array
    {
        $response = $this->client()->get(self::BASE_URL . '/transactions/verify_by_reference', [
            'tx_ref' => $txRef,
        ]);

        if (! $response->successful()) {
            Log::warning('Flutterwave verification unavailable', [
                'tx_ref' => $txRef,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response->json('data');
    }

    /**
     * Whether a webhook really came from Flutterwave.
     *
     * Fails closed. The previous implementation skipped the check entirely when
     * the shared secret was unset, so an unconfigured deployment accepted any
     * caller's word that a payment had succeeded.
     */
    public function verifySignature(?string $providedHash): bool
    {
        $expected = config('services.flutterwave.webhook_hash');

        if (blank($expected)) {
            Log::error('Flutterwave webhook rejected: FLW_WEBHOOK_HASH is not configured');

            return false;
        }

        return is_string($providedHash) && hash_equals($expected, $providedHash);
    }

    private function client(): PendingRequest
    {
        return Http::withToken(config('services.flutterwave.secret_key'))
            ->acceptJson()
            ->timeout(20)
            // Money endpoints: retry the blips, but never hammer.
            ->retry(2, 300, throw: false);
    }
}
