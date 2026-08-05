<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API : Nuvo
|--------------------------------------------------------------------------
|
| Three tiers:
|
|   · public        : no credentials, tight rate limits, minimal payloads
|   · authenticated : merchant's own data, guarded by policies
|   · paid          : the same, plus `plan:pro`
|
| Rate limiters live in AppServiceProvider so the budgets are legible in one
| place rather than scattered across route definitions.
|
*/

// ─── Authentication ──────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    // Credential endpoints are the ones worth brute-forcing: keep them narrow.
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/register',        [AuthController::class, 'register']);
        Route::post('/login',           [AuthController::class, 'login']);
        Route::post('/google/callback', [AuthController::class, 'googleCallback']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout',   [AuthController::class, 'logout']);
        Route::get('/me',        [AuthController::class, 'me']);
        Route::put('/profile',   [AuthController::class, 'updateProfile']);
        Route::put('/password',  [AuthController::class, 'updatePassword']);
        Route::delete('/account', [AuthController::class, 'destroyAccount']);
    });
});

// ─── Public booking pages ────────────────────────────────────────────────
Route::prefix('b/{slug}')->middleware('throttle:public')->group(function () {
    Route::get('/',             [PublicBookingController::class, 'show']);
    Route::get('/slots',        [PublicBookingController::class, 'availableSlots']);
    Route::get('/availability', [PublicBookingController::class, 'availability']);

    // Writes: a much smaller budget than reads. Filling a merchant's whole diary
    // from a script was previously a matter of seconds.
    Route::post('/book', [PublicBookingController::class, 'book'])
        ->middleware('throttle:booking');
});

// ─── Customer self-service ───────────────────────────────────────────────
Route::middleware('throttle:tracking')->group(function () {
    Route::post('/track-booking',  [PublicBookingController::class, 'track']);
    Route::post('/cancel-booking', [PublicBookingController::class, 'cancelByCustomer']);
});

// ─── Payment gateway callback ────────────────────────────────────────────
// Unauthenticated by necessity; authenticity comes from the signature check in
// FlutterwaveGateway::verifySignature(), which fails closed.
Route::post('/webhooks/flutterwave', [PaymentController::class, 'webhook']);

// ─── Merchant area ───────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Reachable before a business exists; this is how a Google signup finishes.
    Route::post('/business/setup', [BusinessController::class, 'setup']);

    Route::prefix('payments')->group(function () {
        Route::get('/plans',        [PaymentController::class, 'plans']);
        Route::post('/initiate',    [PaymentController::class, 'initiate']);
        Route::post('/verify',      [PaymentController::class, 'verify']);
        Route::get('/history',      [PaymentController::class, 'history']);
        Route::get('/subscription', [PaymentController::class, 'subscription']);
    });

    // Everything past here needs a configured business.
    Route::middleware('business')->group(function () {
        Route::get('/business', [BusinessController::class, 'show']);
        Route::put('/business', [BusinessController::class, 'update']);

        Route::apiResource('services', ServiceController::class);
        Route::patch('/services/{service}/toggle', [ServiceController::class, 'toggle']);

        // Declared before the {booking} route so "export" is never read as an id.
        Route::get('/bookings/export/csv', [BookingController::class, 'exportCsv']);
        Route::get('/bookings',            [BookingController::class, 'index']);
        Route::get('/bookings/{booking}',  [BookingController::class, 'show']);
        Route::patch('/bookings/{booking}/status', [BookingController::class, 'updateStatus']);
        Route::delete('/bookings/{booking}',       [BookingController::class, 'destroy']);

        Route::get('/dashboard/stats',    [DashboardController::class, 'stats']);
        Route::get('/dashboard/upcoming', [DashboardController::class, 'upcoming']);
        Route::get('/dashboard/chart',    [DashboardController::class, 'chart']);

        // Advanced analytics are what the Pro plan is sold on.
        Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])
            ->middleware('plan:pro');
    });
});
