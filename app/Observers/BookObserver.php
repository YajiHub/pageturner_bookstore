<?php

namespace App\Observers;

use App\Models\Book;
use App\Services\BookCacheService;
use Illuminate\Support\Facades\Cache;

class BookObserver
{
    public function __construct(protected BookCacheService $cacheService) {}

    public function saved(Book $book): void
    {
        $this->cacheService->invalidateCatalog();
        Cache::forget("book:isbn:{$book->isbn}");
        
        if (Cache::supportsTags()) {
            Cache::tags(["category:{$book->category_id}"])->flush();
        }
    }

    public function deleted(Book $book): void
    {
        $this->saved($book);
    }
}