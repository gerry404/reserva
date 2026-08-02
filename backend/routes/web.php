<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| Réserva is a JSON API. The interface is the Vue app, which Caddy serves at
| the same origin, so anything that is not /api or /storage is handled by the
| SPA and never reaches PHP.
|
| /up is registered by bootstrap/app.php (`health: '/up'`) — it is what the
| container healthcheck and the deploy script poll, and redefining it here
| would shadow Laravel's own implementation.
|
*/

Route::fallback(fn () => response()->json([
    'message' => 'Ressource introuvable.',
], 404));
