@extends('layouts.app')

@section('title', 'All Books - PageTurner')

@section('header')
    <h1 class="text-3xl font-extrabold tracking-tight text-gray-900">All Books</h1>
@endsection

@section('content')
{{-- Search and Filter --}}
<div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-8">
    <form action="{{ route('books.index') }}" method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[250px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">Search Catalog</label>
            <input type="text" name="search"
                value="{{ request('search') }}"
                placeholder="Search by title or author..."
                class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
        </div>
        <div class="w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1">Genre</label>
            <select name="category" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="w-32">
            <label class="block text-sm font-medium text-gray-700 mb-1">Min Price</label>
            <input type="number" name="min_price" value="{{ request('min_price') }}" min="0" step="0.01" placeholder="₱0.00" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
        </div>
        <div class="w-32">
            <label class="block text-sm font-medium text-gray-700 mb-1">Max Price</label>
            <input type="number" name="max_price" value="{{ request('max_price') }}" min="0" step="0.01" placeholder="₱1500.00" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
        </div>
        <div class="w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
            <select name="sort" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
                <option value="">Newest Arrivals</option>
                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>Top Rated</option>
            </select>
        </div>
        <div>
            <button type="submit" class="bg-indigo-600 text-white font-medium px-6 py-2.5 rounded-lg hover:bg-indigo-700 transition shadow-sm w-full">
                Apply Filters
            </button>
        </div>
    </form>
</div>

{{-- Books Grid --}}
@if($books->count() > 0)
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-8">
        @foreach($books as $book)
            <x-book-card :book="$book" />
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-12">
        {{ $books->withQueryString()->links() }}
    </div>
@else
    <div class="text-center py-16 bg-white rounded-2xl border border-gray-100">
        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No books found</h3>
        <p class="mt-1 text-sm text-gray-500">We couldn't find any books matching your current filter criteria.</p>
        <div class="mt-6">
            <a href="{{ route('books.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200">
                Clear Filters
            </a>
        </div>
    </div>
@endif
@endsection
