<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Return available plans and pricing.
     */
    public function plans(): JsonResponse
    {
        return response()->json([
            'plans'    => Payment::PLANS,
            'currency' => 'XAF',
        ]);
    }

    /**
     * Initiate a Flutterwave payment.
     */
    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan'          => 'required|in:pro,business',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $user   = $request->user();
        $plan   = $validated['plan'];
        $cycle  = $validated['billing_cycle'];
        $amount = Payment::PLANS[$plan][$cycle];
        $txRef  = Payment::generateTxRef();

        // Save payment record
        $payment = Payment::create([
            'user_id'       => $user->id,
            'tx_ref'        => $txRef,
            'plan'          => $plan,
            'billing_cycle' => $cycle,
            'amount'        => $amount,
            'currency'      => 'XAF',
            'status'        => 'pending',
        ]);

        // Call Flutterwave Standard Payment API
        $response = Http::withToken(config('services.flutterwave.secret_key'))
            ->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref'         => $txRef,
                'amount'         => $amount,
                'currency'       => 'XAF',
                'redirect_url'   => config('app.frontend_url') . '/dashboard/billing?status=callback',
                'customer'       => [
                    'email' => $user->email,
                    'name'  => $user->name,
                    'phonenumber' => $user->phone ?? '',
                ],
                'customizations' => [
                    'title'       => 'Réserva ' . Payment::PLANS[$plan]['label'],
                    'description' => 'Abonnement ' . Payment::PLANS[$plan]['label'] . ' — ' . ($cycle === 'monthly' ? 'Mensuel' : 'Annuel'),
                    'logo'        => config('app.frontend_url') . '/logo.png',
                ],
                'payment_options' => 'mobilemoneycm,card',
                'meta' => [
                    'user_id'    => $user->id,
                    'plan'       => $plan,
                    'cycle'      => $cycle,
                    'payment_id' => $payment->id,
                ],
            ]);

        if (!$response->ok() || $response->json('status') !== 'success') {
            Log::error('Flutterwave initiate failed', [
                'response' => $response->json(),
                'user_id'  => $user->id,
            ]);
            $payment->update(['status' => 'failed']);
            return response()->json(['message' => 'Impossible d\'initier le paiement. Réessayez.'], 500);
        }

        return response()->json([
            'payment_link' => $response->json('data.link'),
            'tx_ref'       => $txRef,
        ]);
    }

    /**
     * Verify payment after redirect from Flutterwave.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'tx_ref' => 'required|string',
        ]);

        $payment = Payment::where('tx_ref', $request->tx_ref)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($payment->status === 'successful') {
            return response()->json([
                'status'  => 'successful',
                'message' => 'Ce paiement a déjà été validé.',
                'plan'    => $payment->plan,
            ]);
        }

        // Verify with Flutterwave by tx_ref
        $response = Http::withToken(config('services.flutterwave.secret_key'))
            ->get("https://api.flutterwave.com/v3/transactions/verify_by_reference", [
                'tx_ref' => $payment->tx_ref,
            ]);

        if (!$response->ok()) {
            return response()->json([
                'status'  => 'pending',
                'message' => 'Paiement en cours de vérification.',
            ]);
        }

        $data   = $response->json('data');
        $status = $data['status'] ?? 'unknown';

        if ($status === 'successful'
            && (int) $data['amount'] >= $payment->amount
            && strtoupper($data['currency']) === $payment->currency
        ) {
            $this->activatePlan($payment, $data);

            return response()->json([
                'status'  => 'successful',
                'message' => 'Paiement confirmé ! Votre plan est actif.',
                'plan'    => $payment->plan,
            ]);
        }

        $payment->update([
            'status'  => $status === 'successful' ? 'failed' : $status,
            'flw_ref' => $data['flw_ref'] ?? null,
            'meta'    => $data,
        ]);

        return response()->json([
            'status'  => $status,
            'message' => 'Le paiement n\'a pas abouti.',
        ]);
    }

    /**
     * Flutterwave webhook.
     */
    public function webhook(Request $request): JsonResponse
    {
        // Verify webhook signature
        $secretHash = config('services.flutterwave.webhook_hash');
        if ($secretHash && $request->header('verif-hash') !== $secretHash) {
            Log::warning('Flutterwave webhook: invalid hash');
            return response()->json(['status' => 'error'], 401);
        }

        $data = $request->input('data');
        if (!$data || ($data['status'] ?? '') !== 'successful') {
            return response()->json(['status' => 'ignored']);
        }

        $txRef = $data['tx_ref'] ?? null;
        if (!$txRef) return response()->json(['status' => 'ignored']);

        $payment = Payment::where('tx_ref', $txRef)->first();
        if (!$payment || $payment->status === 'successful') {
            return response()->json(['status' => 'already_processed']);
        }

        // Double-verify with Flutterwave API
        $verify = Http::withToken(config('services.flutterwave.secret_key'))
            ->get("https://api.flutterwave.com/v3/transactions/{$data['id']}/verify");

        $verifyData = $verify->json('data');
        if (
            $verify->ok()
            && ($verifyData['status'] ?? '') === 'successful'
            && (int) $verifyData['amount'] >= $payment->amount
            && strtoupper($verifyData['currency']) === $payment->currency
        ) {
            $this->activatePlan($payment, $verifyData);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Get user's payment history.
     */
    public function history(Request $request): JsonResponse
    {
        $payments = Payment::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn($p) => [
                'id'            => $p->id,
                'plan'          => Payment::PLANS[$p->plan]['label'] ?? $p->plan,
                'billing_cycle' => $p->billing_cycle === 'monthly' ? 'Mensuel' : 'Annuel',
                'amount'        => $p->amount,
                'currency'      => $p->currency,
                'status'        => $p->status,
                'payment_method' => $p->payment_method,
                'created_at'    => $p->created_at->format('d/m/Y H:i'),
                'paid_at'       => $p->paid_at?->format('d/m/Y H:i'),
            ]);

        return response()->json($payments);
    }

    /**
     * Current subscription info.
     */
    public function subscription(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'plan'            => $user->plan,
            'plan_label'      => match($user->plan) { 'pro' => 'Pro', 'business' => 'Business', default => 'Gratuit' },
            'plan_expires_at' => $user->plan_expires_at?->format('d/m/Y'),
            'is_active'       => $user->plan !== 'free' && (!$user->plan_expires_at || $user->plan_expires_at->isFuture()),
        ]);
    }

    // ─── Private ─────────────────────────────────────────────────────

    private function activatePlan(Payment $payment, array $flwData): void
    {
        $payment->update([
            'status'         => 'successful',
            'flw_ref'        => $flwData['flw_ref'] ?? null,
            'payment_method' => $flwData['payment_type'] ?? null,
            'meta'           => $flwData,
            'paid_at'        => now(),
        ]);

        $user = $payment->user;
        $expiresAt = $payment->billing_cycle === 'yearly'
            ? now()->addYear()
            : now()->addMonth();

        // Extend if already active
        if ($user->plan_expires_at && $user->plan_expires_at->isFuture() && $user->plan === $payment->plan) {
            $expiresAt = $payment->billing_cycle === 'yearly'
                ? $user->plan_expires_at->addYear()
                : $user->plan_expires_at->addMonth();
        }

        $user->update([
            'plan'            => $payment->plan,
            'plan_expires_at' => $expiresAt,
        ]);

        Log::info("Plan activated: user={$user->id}, plan={$payment->plan}, expires={$expiresAt}");
    }
}
