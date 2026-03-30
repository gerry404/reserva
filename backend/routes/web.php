<?php

use Illuminate\Support\Facades\Route;

Route::get('/up', fn() => response()->json(['status' => 'ok']));

// Fallback: SPA handled by Vue frontend
Route::get('/{any}', fn() => response()->json(['message' => 'API only. Use the Vue frontend.']))->where('any', '.*');
