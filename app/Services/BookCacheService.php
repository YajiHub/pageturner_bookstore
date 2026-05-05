<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\Cache;

class BookCacheService
{
    public function getBookByIsbn(string $isbn): ?Book
    {
        return Cache::remember("book:isbn:{$isbn}", 3600, function () use ($isbn) {
            return Book::where('isbn', $isbn)->first();
        });
    }

    public function invalidateCatalog(): void
    {
        if (Cache::supportsTags()) {
            Cache::tags(['catalog'])->flush();
        }
    }
}