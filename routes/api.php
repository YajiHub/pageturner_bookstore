<?php

use App\Http\Controllers\Api\BookApiController;
use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

// Health check endpoint (no throttling for monitoring)
Route::get('/health', [HealthCheckController::class, 'index'])->name('api.health');

Route::middleware(['throttle:public-read'])->group(function () {
    Route::get('/books', [BookApiController::class, 'index'])->name('api.books.index');
    Route::get('/books/{book}', [BookApiController::class, 'show'])->name('api.books.show');
});
