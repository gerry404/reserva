<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guarantees $request->user()->business is present.
 *
 * Signing up with Google creates an account with no business attached, and
 * every dashboard controller used to reach straight for ->business->id — so
 * that account hit a null-property fatal on its very first request. Rather
 * than sprinkle null checks, the routes that need a business say so.
 */
class EnsureBusinessExists
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->business === null) {
            return response()->json([
                'message'      => 'Vous devez d\'abord configurer votre commerce.',
                'needs_setup'  => true,
            ], 409);
        }

        return $next($request);
    }
}
