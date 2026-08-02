<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Accessing an un-eager-loaded relation is a bug, not a convenience.
        // Fatal outside production so it surfaces in development and CI, where
        // it is cheap to fix, rather than as a slow dashboard for a merchant.
        Model::preventLazyLoading(! $this->app->isProduction());

        $this->configurePasswordPolicy();
        $this->configureRateLimiting();
    }

    /**
     * One password policy, applied everywhere Password::defaults() is used:
     * registration, reset and change.
     */
    private function configurePasswordPolicy(): void
    {
        Password::defaults(function () {
            $rule = Password::min(8)->letters()->numbers();

            // Checking against known breach corpora needs an outbound call, so
            // it stays off in tests and local development.
            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });
    }

    /**
     * Request budgets.
     *
     * Laravel 11 applies no throttling unless asked, and this API had none: the
     * login endpoint accepted unlimited password guesses, and the public booking
     * endpoint would let a script fill a merchant's entire diary in seconds.
     *
     * Limits are keyed by IP for anonymous traffic and by user id once signed
     * in, so one abusive visitor cannot exhaust the budget for a whole network.
     */
    private function configureRateLimiting(): void
    {
        // Backstop for everything that does not name a limiter of its own.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($this->fingerprint($request)));

        // Credentials. Generous enough for a forgetful merchant, useless for a
        // dictionary attack.
        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(10)->by($request->ip()),
            Limit::perMinute(5)->by($request->ip() . '|' . $request->input('email')),
        ]);

        // Reading a public booking page: cheap, cacheable, browsed by real people.
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        // Creating bookings. Nobody books six appointments a minute by hand.
        RateLimiter::for('booking', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perDay(30)->by($request->ip()),
        ]);

        // Reference lookups: the one endpoint that would let someone enumerate
        // booking references, so it gets the tightest budget of the three.
        RateLimiter::for('tracking', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }

    private function fingerprint(Request $request): string
    {
        return $request->user()?->id
            ? 'user:' . $request->user()->id
            : 'ip:' . $request->ip();
    }
}
