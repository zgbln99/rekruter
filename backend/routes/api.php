<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Publiczne
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('auth.login');

    // Chronione (Sanctum + kontekst tenanta)
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // Kolejne moduły (Faza 1+) dołączane tutaj:
        // Route::apiResource('candidates', CandidateController::class);
        // ...
    });
});
