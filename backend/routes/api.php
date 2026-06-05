<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CandidateController;
use App\Http\Controllers\Api\V1\CandidateLookupController;
use App\Http\Controllers\Api\V1\ContactLogController;
use App\Http\Controllers\Api\V1\TaskController;
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

        // --- Faza 1: rdzeń KPI ---

        // Deduplikacja po numerze (lookup w locie).
        Route::get('candidates/lookup', CandidateLookupController::class)->name('candidates.lookup');

        Route::apiResource('candidates', CandidateController::class)->except(['create', 'edit']);

        // Historia kontaktów (Call Log) per kandydat.
        Route::get('candidates/{candidate}/contacts', [ContactLogController::class, 'index'])
            ->name('candidates.contacts.index');
        Route::post('candidates/{candidate}/contacts', [ContactLogController::class, 'store'])
            ->name('candidates.contacts.store');

        // Zadania (follow-up) — ekran „Dziś".
        Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    });
});
