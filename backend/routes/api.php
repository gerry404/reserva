<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes — Réserva
|--------------------------------------------------------------------------
*/

// Auth
Route::prefix('auth')->group(function () {
    Route::post('/register',        [AuthController::class, 'register']);
    Route::post('/login',           [AuthController::class, 'login']);
    Route::post('/google/callback', [AuthController::class, 'googleCallback']);
});

// Public booking pages (no auth required)
Route::prefix('b/{slug}')->group(function () {
    Route::get('/',       [PublicBookingController::class, 'show']);
    Route::get('/slots',  [PublicBookingController::class, 'availableSlots']);
    Route::post('/book',  [PublicBookingController::class, 'book']);
});

// Flutterwave webhook (no auth)
Route::post('/webhooks/flutterwave', [PaymentController::class, 'webhook']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    // Business profile
    Route::get('/business',     [BusinessController::class, 'show']);
    Route::put('/business',     [BusinessController::class, 'update']);
    Route::post('/business/setup', [BusinessController::class, 'setup']);

    // Services
    Route::apiResource('services', ServiceController::class);
    Route::patch('/services/{service}/toggle', [ServiceController::class, 'toggle']);

    // Bookings
    Route::get('/bookings',                        [BookingController::class, 'index']);
    Route::get('/bookings/{booking}',              [BookingController::class, 'show']);
    Route::patch('/bookings/{booking}/status',     [BookingController::class, 'updateStatus']);
    Route::delete('/bookings/{booking}',           [BookingController::class, 'destroy']);
    Route::get('/bookings/export/csv',             [BookingController::class, 'exportCsv']);

    // Dashboard
    // Payments
    Route::get('/payments/plans',        [PaymentController::class, 'plans']);
    Route::post('/payments/initiate',    [PaymentController::class, 'initiate']);
    Route::post('/payments/verify',      [PaymentController::class, 'verify']);
    Route::get('/payments/history',      [PaymentController::class, 'history']);
    Route::get('/payments/subscription', [PaymentController::class, 'subscription']);

    Route::get('/dashboard/stats',     [DashboardController::class, 'stats']);
    Route::get('/dashboard/upcoming',  [DashboardController::class, 'upcoming']);
    Route::get('/dashboard/chart',     [DashboardController::class, 'chart']);
    Route::get('/dashboard/analytics', [DashboardController::class, 'analytics']);
});
