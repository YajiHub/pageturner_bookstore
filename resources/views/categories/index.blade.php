@extends('layouts.app')

@section('title', 'Categories')

@section('content')
    <h1 class="text-3xl font-bold mb-6">Book Categories</h1>

    @if($categories->isEmpty())
        <x-alert type="info">
            No categories available at the moment. Check back soon!
        </x-alert>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($categories as $category)
                <div class="bg-white p-4 rounded-lg shadow hover:shadow-md transition text-center">
                    <a href="{{ route('categories.show', $category) }}">
                        <h2 class="font-semibold text-gray-800">{{ $category->name }}</h2>
                        <p class="text-sm text-gray-500">{{ $category->books_count }} books</p>
                    </a>
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.categories.edit', $category) }}" class="text-xs text-indigo-600 hover:underline mt-2 inline-block">Edit</a>
                        @endif
                    @endauth
                </div>
            @endforeach
        </div>
        
        <div class="mt-6">
            {{ $categories->links() }}
        </div>
    @endif
@endsection