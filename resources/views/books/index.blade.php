@extends('layouts.app')

@section('title', 'All Books - PageTurner')

@section('header')
    <h1 class="text-3xl font-bold text-gray-900">All Books</h1>
@endsection

@section('content')
{{-- Search and Filter --}}
<div class="bg-white p-4 rounded-lg shadow mb-6">
    <form action="{{ route('books.index') }}" method="GET" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search"
                value="{{ request('search') }}"
                placeholder="Search by title or author..."
                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div class="w-48">
            <select name="category" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="w-32">
            <input type="number" name="min_price" value="{{ request('min_price') }}" min="0" step="0.01" placeholder="Min Price" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div class="w-32">
            <input type="number" name="max_price" value="{{ request('max_price') }}" min="0" step="0.01" placeholder="Max Price" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div class="w-48">
            <select name="sort" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Sort</option>
                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>Top Rated</option>
            </select>
        </div>
        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 transition">
            Search
        </button>
    </form>
</div>

{{-- Books Grid --}}
@if($books->count() > 0)
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @foreach($books as $book)
            <x-book-card :book="$book" />
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-8">
        {{ $books->withQueryString()->links() }}
    </div>
@else
    <x-alert type="info">
        No books found matching your criteria.
    </x-alert>
@endif
@endsection
