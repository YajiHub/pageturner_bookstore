<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Category;

class HomeController extends Controller
{
    public function index()
    {
        // Select featured books by two signals:
        // 1) Highest average rating (only books with at least one review)
        // 2) Most number of reviews
        $booksWithReviews = Book::with('category')->withCount('reviews')->withAvg('reviews', 'rating')->get();

        $topRated = $booksWithReviews->where('reviews_count', '>', 0)
            ->sortByDesc('reviews_avg_rating')
            ->sortByDesc('reviews_count')
            ->take(4);

        $mostReviewed = $booksWithReviews->sortByDesc('reviews_count')->take(4);

        // Merge unique and limit to 8
        $featuredBooks = $topRated->merge($mostReviewed)->unique('id')->values()->take(8);

        $categories = Category::withCount('books')->get();

        return view('home', compact('featuredBooks', 'categories'));
        
    }
}
