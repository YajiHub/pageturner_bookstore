@extends('layouts.app')

@section('title', $category->name)

@section('content')
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('categories.index') }}" class="text-gray-600 hover:text-gray-900">
                &larr; Back to Categories
            </a>
            <h1 class="text-3xl font-bold mt-2">{{ $category->name }}</h1>
            @if($category->description)
                <p class="text-gray-600 mt-1">{{ $category->description }}</p>
            @endif
        </div>
        
        @auth
            @if(auth()->user()->isAdmin())
                <div class="flex space-x-2">
                    <a href="{{ route('admin.categories.edit', $category) }}" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600 transition">
                        Edit
                    </a>
                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Delete this category and all its books?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition">
                            Delete
                        </button>
                    </form>
                </div>
            @endif
        @endauth
    </div>
    
    @if($category->books->isEmpty())
        <x-alert type="info">
            No books available in this category at the moment. Check back soon!
        </x-alert>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($category->books as $book)
                <x-book-card :book="$book" />
            @endforeach
        </div>
    @endif

    {{-- spacer to push footer down --}}
    <div class="h-96"></div>
@endsection
