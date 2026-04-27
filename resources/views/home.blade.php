@extends('layouts.app')

@section('title', 'PageTurner - Online Bookstore')

@section('hero')
<div class="relative bg-gray-900 overflow-hidden py-16 sm:py-24 lg:py-32">
    <!-- Background Image -->
    <div class="absolute inset-0">
        <img class="w-full h-full object-cover opacity-30 mix-blend-multiply" src="https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80" alt="Beautiful library with books">
        <!-- Suble Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-b from-gray-900/60 via-transparent to-gray-900/80"></div>
    </div>
    
    <!-- Hero Content -->
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col justify-center items-center text-center">
        <h1 class="text-5xl font-extrabold tracking-tight text-white sm:text-6xl lg:text-7xl shadow-sm mb-4 drop-shadow-2xl">
            Welcome to PageTurner
        </h1>
        <p class="mt-4 max-w-xl text-xl text-gray-100 lg:text-2xl drop-shadow-lg">
            Discover your next favorite book from our curated collection of inspiring stories.
        </p>
        <div class="mt-10">
            <a href="{{ route('books.index') }}" class="inline-flex items-center justify-center px-8 py-4 border border-transparent text-lg font-medium rounded-full text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 hover:scale-105 transform transition-all duration-300 shadow-2xl">
                Start Reading
                <svg class="ml-2 -mr-1 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
        </div>
    </div>
</div>
@endsection

@section('content')

{{-- Categories Summary (Clean Cards) --}}
<section class="mt-8 mb-16">
    <div class="flex items-center justify-between mb-8 text-gray-800">
        <h2 class="text-3xl font-extrabold tracking-tight">Explore Categories</h2>
        <a href="{{ route('categories.index') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold flex items-center group">
            View All Categories 
            <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        @foreach($categories->take(6) as $category)
            <a href="{{ route('categories.show', $category) }}" class="relative rounded-2xl p-6 bg-white shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transform transition-all duration-300 text-center group overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <h3 class="relative font-bold text-gray-900 group-hover:text-indigo-700 text-lg">{{ $category->name }}</h3>
                <p class="relative text-sm text-gray-500 font-medium mt-1">{{ number_format($category->books_count) }} Titles</p>
            </a>
        @endforeach
    </div>
</section>

{{-- High Concept Featured Books View (One Row, 4 Items) --}}
<section class="mb-20">
    <div class="text-center max-w-3xl mx-auto mb-12">
        <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Featured Collections</h2>
        <p class="mt-4 text-lg text-gray-500">Highest-rated and most critically acclaimed additions hand-picked by our curators.</p>
    </div>

    @forelse($featuredBooks as $book)
        @if($loop->first)
            {{-- Fixed grid explicitly mapped to 4 columns on large screens to satisfy Miller's Law --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-8">
        @endif
                <x-book-card :book="$book" />
        @if($loop->last)
            </div>
        @endif
    @empty
        <div class="text-center py-12 bg-white rounded-2xl border border-gray-100">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No books available</h3>
            <p class="mt-1 text-sm text-gray-500">The collection is currently being updated. Check back soon!</p>
        </div>
    @endforelse
</section>
@endsection
