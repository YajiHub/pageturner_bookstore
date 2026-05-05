<?php

namespace App\Repositories;

use App\Models\Book;
use Illuminate\Contracts\Pagination\CursorPaginator;

class BookRepository
{
    public function getActiveCatalog(int $perPage = 100): CursorPaginator
    {
        return Book::select([
            'books.id', 'books.isbn', 'books.title', 'books.author',
            'books.price', 'books.stock_quantity', 'books.published_at',
            'books.category_id'
        ])
        ->with(['category:id,name,slug'])
        ->where('is_active', true)
        ->orderBy('published_at', 'desc')
        ->orderBy('id', 'desc') // Secondary ID sort critical for stable cursor pagination
        ->cursorPaginate($perPage);
    }
}