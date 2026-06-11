<?php

use App\Http\Controllers\PublicCareersController;
use Illuminate\Support\Facades\Route;

// Strona główna → publiczna strona kariery.
Route::get('/', [PublicCareersController::class, 'index']);

/*
 * Publiczna strona kariery (SSR, indeksowana). Bez auth — pojedyncza agencja.
 */
Route::prefix('kariera')->name('careers.')->group(function () {
    Route::get('/', [PublicCareersController::class, 'index'])->name('index');
    Route::post('/oddzwonimy', [PublicCareersController::class, 'callback'])
        ->middleware('throttle:5,1')
        ->name('callback');
    Route::get('/{slug}/{jobPosting}', [PublicCareersController::class, 'show'])->name('show');
    Route::post('/{jobPosting}/aplikuj', [PublicCareersController::class, 'apply'])
        ->middleware('throttle:10,1')
        ->name('apply');
});

// Polityka prywatności (RODO) — publiczna.
Route::get('/polityka-prywatnosci', [PublicCareersController::class, 'privacy'])->name('careers.privacy');
