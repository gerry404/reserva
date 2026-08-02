<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Services\FlutterwaveGateway;
use App\Services\SubscriptionService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(
        private readonly FlutterwaveGateway $gateway,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function plans(): JsonResponse
    {
        return response()->json([
            'plans'    => Payment::PLANS,
            'currency' => Payment::CURRENCY,
            'symbol'   => Money::symbol(Payment::CURRENCY),
        ]);
    }

    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan'          => ['required', Rule::in(array_keys(Payment::PLANS))],
            'billing_cycle' => ['required', Rule::in(Payment::CYCLES)],
        ]);

        if (! $this->gateway->isConfigured()) {
            Log::error('Payment attempted while Flutterwave is not configured');

            return response()->json([
                'message' => 'Les paiements sont momentanément indisponibles. Réessayez plus tard.',
            ], 503);
        }

        $user = $request->user();

        // The amount is read from our own price list, never from the request:
        // the client says which plan, we say what it costs.
        $payment = Payment::create([
            'user_id'       => $user->id,
            'tx_ref'        => Payment::generateTxRef(),
            'plan'          => $validated['plan'],
            'billing_cycle' => $validated['billing_cycle'],
            'amount'        => Payment::amountFor($validated['plan'], $validated['billing_cycle']),
            'currency'      => Payment::CURRENCY,
            'status'        => Payment::STATUS_PENDING,
        ]);

        $link = $this->gateway->createCheckout($payment, $user);

        if ($link === null) {
            $payment->update(['status' => Payment::STATUS_FAILED]);

            return response()->json([
                'message' => 'Impossible d\'ouvrir la page de paiement. Réessayez dans un instant.',
            ], 502);
        }

        return response()->json([
            'payment_link' => $link,
            'tx_ref'       => $payment->tx_ref,
        ]);
    }

    /**
     * Called by the SPA when the customer lands back from checkout.
     *
     * Scoped to the signed-in user's own payments, so nobody can drive somebody
     * else's transaction through settlement.
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tx_ref' => ['required', 'string', 'max:64'],
        ]);

        $payment = Payment::query()
            ->where('tx_ref', $validated['tx_ref'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($this->subscriptions->settle($payment)) {
            return response()->json([
                'status'  => Payment::STATUS_SUCCESSFUL,
                'message' => 'Paiement confirmé. Votre plan est actif !',
                'plan'    => $payment->plan,
            ]);
        }

        // Still pending is the common case here: mobile money confirmations can
        // land after the browser redirect does.
        $payment->refresh();

        return response()->json([
            'status'  => $payment->status,
            'message' => $payment->status === Payment::STATUS_PENDING
                ? 'Paiement en cours de confirmation. Cela peut prendre une minute.'
                : 'Ce paiement n\'a pas abouti.',
        ]);
    }

    /**
     * Flutterwave server-to-server notification.
     *
     * Always answers 200 once the signature checks out: a non-2xx makes
     * Flutterwave retry, and there is nothing to retry for a payload we have
     * deliberately ignored.
     */
    public function webhook(Request $request): JsonResponse
    {
        if (! $this->gateway->verifySignature($request->header('verif-hash'))) {
            return response()->json(['status' => 'unauthorised'], 401);
        }

        $txRef = $request->input('data.tx_ref');

        if (! is_string($txRef) || $txRef === '') {
            return response()->json(['status' => 'ignored']);
        }

        $payment = Payment::where('tx_ref', $txRef)->first();

        if (! $payment) {
            Log::warning('Flutterwave webhook for an unknown reference', ['tx_ref' => $txRef]);

            return response()->json(['status' => 'ignored']);
        }

        // The webhook body is only a hint that something happened. What settles
        // the payment is our own lookup by tx_ref inside SubscriptionService.
        $this->subscriptions->settle($payment);

        return response()->json(['status' => 'ok']);
    }

    public function history(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(24)
            ->get()
            ->map(fn (Payment $p) => [
                'id'             => $p->id,
                'reference'      => $p->tx_ref,
                'plan'           => Payment::labelFor($p->plan),
                'billing_cycle'  => $p->cycleLabel(),
                'amount'         => $p->amount,
                'formatted_amount' => Money::format($p->amount, $p->currency),
                'status'         => $p->status,
                'payment_method' => $p->payment_method,
                'created_at'     => $p->created_at->toIso8601String(),
                'paid_at'        => $p->paid_at?->toIso8601String(),
            ]);

        return response()->json($payments);
    }

    public function subscription(Request $request): JsonResponse
    {
        $user = $request->user();
        $plan = $user->effectivePlan();

        return response()->json([
            'plan'            => $plan,
            'plan_label'      => $plan === User::PLAN_FREE ? 'Gratuit' : Payment::labelFor($plan),
            'plan_expires_at' => $user->plan_expires_at?->toIso8601String(),
            'is_active'       => $plan !== User::PLAN_FREE,
            'on_trial'        => $user->onTrial(),
            'days_remaining'  => $user->plan_expires_at && $user->plan_expires_at->isFuture()
                ? (int) ceil(now()->diffInDays($user->plan_expires_at, absolute: true))
                : 0,
        ]);
    }
}
