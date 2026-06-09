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
    Route::get('/{slug}/{jobPosting}', [PublicCareersController::class, 'show'])->name('show');
    Route::post('/{jobPosting}/aplikuj', [PublicCareersController::class, 'apply'])
        ->middleware('throttle:10,1')
        ->name('apply');
});
