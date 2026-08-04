<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Turning a Flutterwave transaction into an active subscription.
 *
 * This is the only place a plan is ever granted, and it is deliberately
 * paranoid. The previous flow re-verified whatever transaction id the caller
 * put in the webhook body and extended the plan whenever that came back
 * successful, so one real payment, replayed, bought a subscription forever.
 *
 * Four things must all hold before a plan is granted:
 *
 *   1. the transaction Flutterwave describes carries *our* tx_ref;
 *   2. it is marked successful;
 *   3. the amount is at least what we asked for, in the currency we asked for;
 *   4. that gateway transaction has not already been settled here.
 *
 * (4) is enforced by a unique index on payments.flw_transaction_id, so it holds
 * even if two webhooks and a browser redirect arrive at the same instant.
 */
class SubscriptionService
{
    public function __construct(
        private readonly FlutterwaveGateway $gateway,
    ) {}

    /**
     * Settle a pending payment against the gateway.
     *
     * @return bool Whether the payment is (now or already was) successful.
     */
    public function settle(Payment $payment): bool
    {
        if ($payment->isSuccessful()) {
            return true;
        }

        $transaction = $this->gateway->findByReference($payment->tx_ref);

        if ($transaction === null) {
            // Gateway unreachable: say nothing, change nothing, let the caller
            // report "still checking". Marking it failed here would strand a
            // payment the customer really made.
            return false;
        }

        if (! $this->describesPayment($transaction, $payment)) {
            $this->reject($payment, $transaction);

            return false;
        }

        return $this->activate($payment, $transaction);
    }

    /**
     * Does this gateway transaction actually settle this payment?
     *
     * @param  array<string, mixed>  $transaction
     */
    private function describesPayment(array $transaction, Payment $payment): bool
    {
        // Rule 1: the one check whose absence made replay possible.
        if (($transaction['tx_ref'] ?? null) !== $payment->tx_ref) {
            Log::warning('Flutterwave transaction does not match the payment it was offered for', [
                'expected' => $payment->tx_ref,
                'received' => $transaction['tx_ref'] ?? null,
            ]);

            return false;
        }

        if (($transaction['status'] ?? null) !== 'successful') {
            return false;
        }

        // Rule 3: underpayment buys nothing; overpayment is the customer's call.
        $paid = (float) ($transaction['amount'] ?? 0);
        if ($paid < (float) $payment->amount) {
            Log::warning('Flutterwave transaction underpaid', [
                'tx_ref'   => $payment->tx_ref,
                'expected' => $payment->amount,
                'paid'     => $paid,
            ]);

            return false;
        }

        return strtoupper((string) ($transaction['currency'] ?? '')) === strtoupper($payment->currency);
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    private function activate(Payment $payment, array $transaction): bool
    {
        return DB::transaction(function () use ($payment, $transaction) {
            // Re-read under a lock: a webhook and the browser redirect routinely
            // race, and only one of them may extend the subscription.
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();

            if ($payment->isSuccessful()) {
                return true;
            }

            $transactionId = (string) ($transaction['id'] ?? '');

            // Rule 4: this gateway transaction may settle exactly one payment.
            if ($transactionId !== '' && Payment::where('flw_transaction_id', $transactionId)->exists()) {
                Log::warning('Flutterwave transaction already settled another payment', [
                    'transaction_id' => $transactionId,
                    'tx_ref'         => $payment->tx_ref,
                ]);

                return false;
            }

            $payment->update([
                'status'             => Payment::STATUS_SUCCESSFUL,
                'flw_ref'            => $transaction['flw_ref'] ?? null,
                'flw_transaction_id' => $transactionId ?: null,
                'payment_method'     => $transaction['payment_type'] ?? null,
                'meta'               => $transaction,
                'paid_at'            => now(),
            ]);

            $user = User::query()->whereKey($payment->user_id)->lockForUpdate()->first();
            $user->update([
                'plan'            => $payment->plan,
                'plan_expires_at' => $this->nextExpiry($user, $payment),
            ]);

            Log::info('Subscription activated', [
                'user'    => $user->id,
                'plan'    => $payment->plan,
                'cycle'   => $payment->billing_cycle,
                'expires' => $user->plan_expires_at?->toDateString(),
            ]);

            return true;
        });
    }

    /**
     * When the new term ends.
     *
     * Renewing the same plan early stacks onto whatever is left, so a merchant
     * who pays a week ahead of expiry does not donate that week to us. Switching
     * plans starts a fresh term from today instead.
     */
    private function nextExpiry(User $user, Payment $payment): Carbon
    {
        $stacksOnRemaining = $user->plan === $payment->plan
            && $user->plan_expires_at
            && $user->plan_expires_at->isFuture();

        $from = $stacksOnRemaining ? $user->plan_expires_at->copy() : now();

        return $payment->billing_cycle === Payment::CYCLE_YEARLY
            ? $from->addYear()
            : $from->addMonthNoOverflow();
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    private function reject(Payment $payment, array $transaction): void
    {
        $status = $transaction['status'] ?? 'failed';

        $payment->update([
            'status'  => in_array($status, [Payment::STATUS_FAILED, Payment::STATUS_CANCELLED], true)
                ? $status
                : Payment::STATUS_FAILED,
            'flw_ref' => $transaction['flw_ref'] ?? null,
            'meta'    => $transaction,
        ]);
    }
}
