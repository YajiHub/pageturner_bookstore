<?php

use App\Http\Controllers\Api\BookApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:public-read'])->group(function () {
    Route::get('/books', [BookApiController::class, 'index'])->name('api.books.index');
    Route::get('/books/{book}', [BookApiController::class, 'show'])->name('api.books.show');
});
