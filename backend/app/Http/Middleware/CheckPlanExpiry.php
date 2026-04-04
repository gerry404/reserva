<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPlanExpiry
{
    /**
     * Auto-downgrade expired plans on each authenticated request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user
            && $user->plan !== 'free'
            && $user->plan_expires_at
            && $user->plan_expires_at->isPast()
        ) {
            $user->update([
                'plan'            => 'free',
                'plan_expires_at' => null,
            ]);
            // Refresh the model so the rest of the request sees 'free'
            $user->refresh();
        }

        return $next($request);
    }
}
