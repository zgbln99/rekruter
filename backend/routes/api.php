<?php

use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CandidateController;
use App\Http\Controllers\Api\V1\CandidateLookupController;
use App\Http\Controllers\Api\V1\ContactLogController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\ProfileController;
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

        // --- Faza 2: Dokumenty + Profil ---

        // Dokumenty kandydata (S3, prywatne).
        Route::get('candidates/{candidate}/documents', [DocumentController::class, 'index'])
            ->name('candidates.documents.index');
        Route::post('candidates/{candidate}/documents', [DocumentController::class, 'store'])
            ->name('candidates.documents.store');
        Route::get('candidates/{candidate}/documents/{document}/download', [DocumentController::class, 'download'])
            ->name('candidates.documents.download');
        Route::delete('candidates/{candidate}/documents/{document}', [DocumentController::class, 'destroy'])
            ->name('candidates.documents.destroy');

        // Zdjęcie profilowe (CropperJS).
        Route::post('candidates/{candidate}/profile-photo', [DocumentController::class, 'storeProfilePhoto'])
            ->name('candidates.profile-photo.store');

        // Profil PDF (podgląd) i wysyłka do klienta.
        Route::get('candidates/{candidate}/profile-pdf', [ProfileController::class, 'pdf'])
            ->name('candidates.profile-pdf');
        Route::post('candidates/{candidate}/profile-send', [ProfileController::class, 'send'])
            ->name('candidates.profile-send');

        // Audit log kandydata.
        Route::get('candidates/{candidate}/activities', [ActivityController::class, 'forCandidate'])
            ->name('candidates.activities');
    });
});
