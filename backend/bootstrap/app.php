<?php

use App\Exceptions\BookingException;
use App\Http\Middleware\EnsureBusinessExists;
use App\Http\Middleware\RequiresPlan;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        /*
         * Caddy terminates TLS and forwards to this container over the Docker
         * network. Without trusting it, every client looks like the proxy,
         * which breaks HTTPS URL generation and collapses per-IP rate limiting
         * into a single shared bucket.
         *
         * Scoped to private ranges rather than '*': only something already
         * inside the network can claim to be a proxy, so a spoofed
         * X-Forwarded-For from the public internet is ignored.
         */
        $middleware->trustProxies(at: [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.1/8',
            '::1/128',
        ]);

        // Laravel 11 ships no API throttling by default. Budgets: AppServiceProvider.
        $middleware->throttleApi();

        $middleware->alias([
            'business' => EnsureBusinessExists::class,
            'plan'     => RequiresPlan::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /*
         * This is a pure JSON API: every failure leaves through here with a
         * shape the SPA can rely on: a `message` it can show a merchant, and
         * `errors` when the problem is per-field.
         */

        $exceptions->render(function (BookingException $e, Request $request) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            return response()->json([
                'message' => $e->validator->errors()->first(),
                'errors'  => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json(['message' => 'Session expirée. Reconnectez-vous.'], 401);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            return response()->json(['message' => 'Vous n\'avez pas accès à cette ressource.'], 403);
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) {
            return response()->json(['message' => 'Ressource introuvable.'], 404);
        });

        /*
         * Anything unplanned. The class and message are logged with the route
         * that produced them; the client is told only that something broke,
         * because stack traces belong in the log, not in a response body.
         */
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof HttpExceptionInterface) {
                return null; // 419, 429, and friends already carry a sane status.
            }

            Log::error('Unhandled exception', [
                'exception' => $e::class,
                'message'   => $e->getMessage(),
                'route'     => $request->method() . ' ' . $request->path(),
                'user'      => $request->user()?->id,
            ]);

            if (config('app.debug')) {
                return null; // Let the debug renderer do its job locally.
            }

            return response()->json([
                'message' => 'Une erreur est survenue. Réessayez dans un instant.',
            ], 500);
        });
    })->create();
