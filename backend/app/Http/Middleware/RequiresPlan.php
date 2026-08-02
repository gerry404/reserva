<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate a route behind a paid plan: `->middleware('plan:pro')`.
 *
 * Réserva sold advanced statistics, unlimited exports and custom links as Pro
 * features while every free account could reach all of them, which left no
 * reason to pay. Entitlement is read from User::effectivePlan(), so an expired
 * subscription loses access the moment it lapses rather than when a nightly job
 * happens to run.
 */
class RequiresPlan
{
    public function handle(Request $request, Closure $next, string $plan = User::PLAN_PRO): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasPlan($plan)) {
            return response()->json([
                'message'       => 'Cette fonctionnalité est réservée aux abonnés ' . ucfirst($plan) . '.',
                'required_plan' => $plan,
                'current_plan'  => $user?->effectivePlan() ?? User::PLAN_FREE,
                'upgrade_url'   => config('app.frontend_url') . '/dashboard/billing',
            ], 402);
        }

        return $next($request);
    }
}
