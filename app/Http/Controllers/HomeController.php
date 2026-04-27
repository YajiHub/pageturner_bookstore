<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;

class HomeController extends Controller
{
    public function index()
    {
        // Select featured books using SQL ordering instead of loading every book
        // into memory. This keeps the home page fast as the catalog grows.
        $baseQuery = Book::with('category')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        $topRated = (clone $baseQuery)
            ->whereHas('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->take(4)
            ->get();

        $mostReviewed = (clone $baseQuery)
            ->orderByDesc('reviews_count')
            ->take(4)
            ->get();

        // Merge unique and limit to exactly 4 for optimal user focus
        $featuredBooks = $topRated->merge($mostReviewed)->unique('id')->values()->take(4);

        $categories = Category::withCount('books')->get();

        return view('home', compact('featuredBooks', 'categories'));

    }
}
