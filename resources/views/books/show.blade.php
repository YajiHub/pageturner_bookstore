@extends('layouts.app')

@section('title', $book->title . ' - PageTurner')

@section('content')
<div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <div class="md:flex">
        {{-- Book Cover --}}
        <div class="md:w-1/3 bg-gray-200 p-8 flex items-center justify-center">
            @if($book->cover_image)
                <img src="{{ asset('storage/' . $book->cover_image) }}" alt="{{ $book->title }}" class="max-h-96 object-contain">
            @else
                <svg class="h-48 w-48 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            @endif
        </div>

        {{-- Book Details --}}
        <div class="md:w-2/3 p-8">
            <span class="text-indigo-600 text-sm font-medium">{{ $book->category->name }}</span>
            <h1 class="text-3xl font-bold text-gray-900 mt-2">{{ $book->title }}</h1>
            <p class="text-xl text-gray-600 mt-1">by {{ $book->author }}</p>

            {{-- Rating --}}
            <div class="flex items-center mt-4">
                @for($i = 1; $i <= 5; $i++)
                    <svg class="h-6 w-6 {{ $i <= round($book->average_rating) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                @endfor
                <span class="ml-2 text-gray-600">{{ number_format($book->average_rating, 1) }} ({{ $book->reviews->count() }} reviews)</span>
            </div>

            <p class="text-3xl font-bold text-indigo-600 mt-4">${{ number_format($book->price, 2) }}</p>

            <div class="mt-4">
                <span class="text-sm {{ $book->stock_quantity > 0 ? 'text-green-600' : 'text-red-600' }}">
                    @if($book->stock_quantity > 0)
                        In Stock ({{ $book->stock_quantity }} available)
                    @else
                        Out of Stock
                    @endif
                </span>
            </div>

            <div class="mt-4">
                <p class="text-gray-600 text-sm"><strong>ISBN:</strong> {{ $book->isbn }}</p>
            </div>

            <div class="mt-6">
                <h3 class="font-semibold text-gray-800">Description</h3>
                <p class="text-gray-600 mt-2">{{ $book->description }}</p>
                @auth
                    @if($book->stock_quantity > 0)
                            @unless(auth()->check() && auth()->user()->isAdmin())
                                <div class="mt-4 flex items-center gap-3">
                                    <form action="{{ route('cart.add', $book) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="bg-gray-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-md text-sm font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                            Add to Cart
                                        </button>
                                    </form>

                                    <form action="{{ route('orders.store') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="book_id" value="{{ $book->id }}">
                                        <button type="submit" class="bg-gray-800 hover:bg-gray-700 text-white px-3 py-2 rounded-md text-sm font-medium">
                                            Buy Now
                                        </button>
                                    </form>

                                    @if($hasPurchased)
                                        <span class="ml-4 text-green-600 text-sm">✓ You own this book</span>
                                    @endif
                                </div>
                            @endunless
                    @else
                        <button disabled class="bg-gray-400 text-white px-3 py-2 rounded-md text-sm cursor-not-allowed">Out of Stock</button>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-md text-sm font-medium">Login to Order</a>
                @endauth
            </div>

            {{-- Admin Actions --}}
            @auth
                @if(auth()->user()->isAdmin())
                    <div class="mt-6 flex space-x-4">
                        <a href="{{ route('admin.books.edit', $book) }}" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600 transition">
                            Edit Book
                        </a>
                        <form action="{{ route('admin.books.destroy', $book) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition">
                                Delete Book
                            </button>
                        </form>
                    </div>
                @endif
            @endauth
        </div>
    </div>
</div>

{{-- Reviews Section --}}
<div class="mt-8" id="reviews">
    <h2 class="text-2xl font-bold mb-6">Customer Reviews</h2>

    {{-- Review Form (only for users who purchased this book) --}}
    @auth
        @if($hasPurchased)
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="font-semibold text-lg mb-4">Write a Review</h3>
                <form action="{{ route('reviews.store', $book) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Rating</label>
                        <select name="rating" class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            <option value="">Select rating</option>
                            @for($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}">{{ $i }} Star{{ $i > 1 ? 's' : '' }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Comment</label>
                        <textarea name="comment" rows="4" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Share your thoughts about this book..."></textarea>
                    </div>
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 transition">
                        Submit Review
                    </button>
                </form>
            </div>
        @else
            @unless(auth()->user()->isAdmin())
                <x-alert type="info" class="mb-6">
                    You can write a review once your order for this book is completed.
                </x-alert>
            @endunless
            {{-- <x-alert type="info" class="mb-6">
                You can write a review once your order for this book is completed.
            </x-alert> --}}
        @endif
    @else
        <x-alert type="info" class="mb-6">
            <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Login</a> to write a review.
        </x-alert>
    @endauth

    {{-- Display Reviews --}}
    @forelse($book->reviews as $review)
        <div class="bg-white rounded-lg shadow p-6 mb-4">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-semibold">{{ $review->user?->name ?? 'Deleted User' }}</p>
                    <div class="flex items-center mt-1">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="h-4 w-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-gray-500 text-sm">{{ $review->created_at->diffForHumans() }}</span>
                    @auth
                        @if(auth()->id() === $review->user_id || auth()->user()->isAdmin())
                            <form action="{{ route('reviews.destroy', $review) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                            </form>
                        @endif
                    @endauth
                </div>
            </div>
            @isset($review->comment)
                <p class="text-gray-600 mt-3">{{ $review->comment }}</p>
            @endisset
        </div>
    @empty
        @if(!auth()->check() || !auth()->user()->isAdmin())
            <x-alert type="info">
                No reviews yet. Be the first to review this book!
            </x-alert>
        @endif
    @endforelse
</div>
@endsection
