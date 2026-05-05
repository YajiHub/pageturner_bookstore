<?php

namespace App\Jobs;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class WarmCategoryCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $categoryId) {}

    public function handle(): void
    {
        $books = Book::select(['id', 'title', 'author', 'price', 'stock_quantity'])
            ->where('category_id', $this->categoryId)
            ->where('is_active', true)
            ->orderBy('published_at', 'desc')
            ->limit(1000)
            ->get();

        if (Cache::supportsTags()) {
            Cache::tags(["category:{$this->categoryId}"])
                ->put("category:{$this->categoryId}:popular", $books, 7200);
        }
    }
}