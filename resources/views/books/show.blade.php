@extends('layouts.app')

@section('title', $book->title . ' - PageTurner Bookstore')

@php
    // Calculate average rating (only from visible reviews - not flagged by AI)
    $visibleReviews = $book->reviews->where('is_flagged_by_ai', false);
    $avgRating = $visibleReviews->avg('rating') ?? 0;
    $reviewCount = $visibleReviews->count();
    $roundedRating = round($avgRating, 1);
@endphp

@section('content')
    <nav class="mb-6 flex items-center space-x-2 text-sm text-gray-600">
        <a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a>
        <span>/</span>
        <a href="{{ route('books.index') }}" class="hover:text-indigo-600">Books</a>
        <span>/</span>
        <span class="text-gray-900 font-medium truncate">{{ $book->title }}</span>
    </nav>

    @if (session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-12 mb-12">
        <div class="lg:col-span-2">
            <div class="bg-gray-100 rounded-xl overflow-hidden shadow-2xl sticky top-20 border border-gray-200 max-w-xs mx-auto lg:mx-0">
                @if($book->cover_image)
                    <img src="{{ Storage::url($book->cover_image) }}" alt="{{ $book->title }}" class="w-full h-auto object-cover aspect-[3/4]">
                @else
                    <div class="w-full aspect-[3/4] bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
                        <div class="text-center">
                            <svg class="w-16 h-16 text-indigo-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C6.5 6.253 2 10.998 2 17s4.5 10.747 10 10.747c5.5 0 10-4.998 10-10.747S17.5 6.253 12 6.253z"></path>
                            </svg>
                            <p class="text-indigo-400 text-sm font-medium">Book Cover</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="lg:col-span-3">
            <div class="mb-4">
                <div class="inline-block bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-xs font-semibold mb-2">
                    {{ $book->category->name ?? 'Uncategorized' }}
                </div>
                <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-2">{{ $book->title }}</h1>
                <p class="text-xl text-gray-600 mb-1">by <span class="font-semibold">{{ $book->author }}</span></p>
            </div>

            <div class="flex items-center gap-4 pb-4 border-b border-gray-200 mb-4">
                <div class="flex items-center gap-1">
                    @for ($i = 1; $i <= 5; $i++)
                        <svg class="w-5 h-5 {{ $i <= $roundedRating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    @endfor
                    <span class="ml-2 font-semibold text-gray-900">{{ $roundedRating > 0 ? number_format($roundedRating, 1) : '0.0' }}</span>
                </div>
                <span class="text-gray-600">{{ $reviewCount }} {{ Str::plural('review', $reviewCount) }}</span>
            </div>

            <div class="mb-6">
                <div class="flex items-baseline gap-4 mb-3">
                    <span class="text-3xl font-bold text-indigo-600">₱{{ number_format($book->price, 2) }}</span>
                    <span class="text-sm text-gray-500">+ Shipping</span>
                </div>
                <div class="flex items-center gap-2">
                    @if($book->stock_quantity > 0)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <span class="w-2 h-2 bg-green-600 rounded-full mr-2"></span>
                            In Stock ({{ $book->stock_quantity }} available)
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            <span class="w-2 h-2 bg-red-600 rounded-full mr-2"></span>
                            Out of Stock
                        </span>
                    @endif
                </div>
            </div>

            <div class="mb-8">
                @if($book->stock_quantity > 0)
                    <form action="{{ route('cart.add', $book) }}" method="POST" class="flex gap-3">
                        @csrf
                        <input type="number" name="quantity" value="1" min="1" max="{{ $book->stock_quantity }}" class="w-20 px-4 py-3 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition-all duration-200 flex items-center justify-center gap-2 shadow-md hover:shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Add to Cart
                        </button>
                    </form>
                @else
                    <button disabled class="w-full bg-gray-300 text-gray-500 font-bold py-3 px-6 rounded-lg cursor-not-allowed">
                        Out of Stock
                    </button>
                @endif
            </div>

            <div class="bg-gray-50 rounded-lg p-6 space-y-4 text-sm">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-500 font-medium">ISBN</p>
                        <p class="text-gray-900 font-mono">{{ $book->isbn }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 font-medium">Format</p>
                        <p class="text-gray-900">{{ $book->format ?? 'Paperback' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 font-medium">Publisher</p>
                        <p class="text-gray-900">{{ $book->publisher ?? 'Unknown' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 font-medium">Published</p>
                        <p class="text-gray-900">{{ \Carbon\Carbon::parse($book->published_at)->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-8 mb-12 border border-gray-100">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">About This Book</h2>
        <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed">
            {{ $book->description }}
        </div>
    </div>

    @php
        try {
            $aiInsight = \Illuminate\Support\Facades\DB::table('ai_book_insights')->where('book_id', $book->id)->first();
        } catch (\Exception $e) {
            $aiInsight = null;
        }
    @endphp

    @if($aiInsight && isset($aiInsight->ai_summary))
        @php
            $sentiment = $aiInsight->overall_sentiment ?? 'Neutral';
            $sentimentClasses = match($sentiment) {
                'Positive' => 'bg-green-50 border-green-300 text-green-900',
                'Negative' => 'bg-red-50 border-red-300 text-red-900',
                'Mixed' => 'bg-amber-50 border-amber-300 text-amber-900',
                default => 'bg-blue-50 border-blue-300 text-blue-900',
            };
            $sentimentBadgeClasses = match($sentiment) {
                'Positive' => 'bg-green-100 text-green-800',
                'Negative' => 'bg-red-100 text-red-800',
                'Mixed' => 'bg-amber-100 text-amber-800',
                default => 'bg-blue-100 text-blue-800',
            };
            $sentimentIcon = match($sentiment) {
                'Positive' => '👍',
                'Negative' => '👎',
                'Mixed' => '➖',
                default => '✨',
            };
        @endphp
        <div class="{{ $sentimentClasses }} rounded-xl border-2 p-8 mb-12 backdrop-blur-sm">
            <div class="flex items-start gap-6">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-xl shadow-md">
                        {{ $sentimentIcon }}
                    </div>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <h3 class="text-xl font-bold">What Readers Think</h3>
                        <span class="{{ $sentimentBadgeClasses }} px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide">
                            {{ $sentiment }}
                        </span>
                    </div>
                    <p class="text-base leading-relaxed mb-4 opacity-95">{{ $aiInsight->ai_summary }}</p>
                    <div class="flex items-center gap-4 text-xs opacity-75 font-medium">
                        <span>✨ {{ $aiInsight->reviews_analyzed_count ?? 0 }} reviews analyzed</span>
                        <span class="text-gray-400">•</span>
                        <span>🤖 AI-Powered</span>
                        <span class="text-gray-400">•</span>
                        <span>Last updated {{ $aiInsight->updated_at ? \Carbon\Carbon::parse($aiInsight->updated_at)->diffForHumans() : 'today' }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Customer Reviews</h2>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="bg-gray-50 rounded-lg p-6 border border-gray-200 lg:col-span-1">
                <div class="text-center mb-6">
                    <div class="text-4xl font-bold text-gray-900 mb-2">{{ $roundedRating > 0 ? number_format($roundedRating, 1) : '0.0' }}</div>
                    <div class="flex justify-center gap-1 mb-2">
                        @for ($i = 1; $i <= 5; $i++)
                            <svg class="w-4 h-4 {{ $i <= round($avgRating) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                        @endfor
                    </div>
                    <p class="text-sm text-gray-600">Based on {{ $reviewCount }} reviews</p>
                </div>

                <div class="space-y-3">
                    @php
                        // FIX: Explicitly query $visibleReviews so hidden 1-star ratings don't corrupt the math!
                        $ratingCounts = [
                            5 => $visibleReviews->where('rating', 5)->count(),
                            4 => $visibleReviews->where('rating', 4)->count(),
                            3 => $visibleReviews->where('rating', 3)->count(),
                            2 => $visibleReviews->where('rating', 2)->count(),
                            1 => $visibleReviews->where('rating', 1)->count(),
                        ];
                    @endphp
                    @foreach([5,4,3,2,1] as $stars)
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-600 w-6">{{ $stars }}★</span>
                            <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-yellow-400" style="width: {{ $reviewCount > 0 ? ($ratingCounts[$stars] / $reviewCount * 100) : 0 }}%"></div>
                            </div>
                            <span class="text-xs text-gray-500 w-8 text-right">{{ $ratingCounts[$stars] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="lg:col-span-2">
                @auth
                    @if($hasPurchased || auth()->user()->isAdmin())
                        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6 mb-6">
                            <h3 class="font-bold text-gray-900 mb-4">Share Your Thoughts</h3>
                            <form action="{{ route('reviews.store', $book) }}" method="POST">
                                @csrf
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Your Rating</label>
                                        <select name="rating" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                            <option value="" selected disabled>Select rating...</option>
                                            <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                                            <option value="4">⭐⭐⭐⭐ Good</option>
                                            <option value="3">⭐⭐⭐ Average</option>
                                            <option value="2">⭐⭐ Poor</option>
                                            <option value="1">⭐ Terrible</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Your Review</label>
                                    <textarea name="comment" rows="4" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Share your honest thoughts about this book..."></textarea>
                                </div>
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                                    Post Review
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                            <p class="text-sm text-gray-700">
                                <span class="font-semibold">Must purchase to review.</span> You need to buy this book to leave a review.
                            </p>
                        </div>
                    @endif
                @else
                    <div class="bg-gray-100 border border-gray-300 rounded-lg p-4 mb-6">
                        <p class="text-sm text-gray-700">
                            <a href="{{ route('login') }}" class="text-indigo-600 hover:underline font-semibold">Log in</a> to write a review.
                        </p>
                    </div>
                @endauth

                <div class="space-y-6">
                    @forelse($book->reviews->sortByDesc('created_at') as $review)
                        @if(!$review->is_flagged_by_ai)
                            <div class="border border-gray-200 rounded-lg p-5 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <p class="font-bold text-gray-900">{{ $review->user->name ?? 'Anonymous' }}</p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <div class="flex gap-0.5">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                    </svg>
                                                @endfor
                                            </div>
                                            <span class="text-xs text-gray-500 ml-2">{{ $review->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                    @if(auth()->check() && (auth()->id() === $review->user_id || auth()->user()->role === 'admin'))
                                        <form action="{{ route('reviews.destroy', $review) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium" onclick="return confirm('Delete this review?')">Delete</button>
                                        </form>
                                    @endif
                                </div>
                                
                                @if(trim($review->comment))
                                    <p class="text-gray-800 mt-3">{{ $review->comment }}</p>
                                @else
                                    <p class="text-gray-400 italic text-sm mt-3 border-l-2 border-gray-200 pl-3">This customer rated this book {{ $review->rating }} stars without leaving a written comment.</p>
                                @endif
                            </div>
                        @elseif(auth()->check() && auth()->user()->isAdmin())
                            <div x-data="{ showText: false }" class="bg-red-50 border-2 border-red-300 rounded-xl shadow-sm relative overflow-hidden transition-all duration-300">
                                <div class="absolute top-0 right-0 bg-red-500 text-white text-xs font-black px-3 py-1 rounded-bl-lg uppercase tracking-wider shadow-sm z-10">
                                    Quarantined
                                </div>
                                <div class="p-5">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-3">
                                        <span class="text-red-800 font-extrabold text-xs flex items-center bg-red-100 px-3 py-1.5 rounded-md border border-red-200 shadow-sm inline-flex w-fit">
                                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                            HIDDEN BY AI: {{ $review->ai_moderation_reason }}
                                        </span>
                                        
                                        <button @click="showText = !showText" type="button" class="text-xs font-bold text-gray-700 bg-white border border-gray-300 px-3 py-1.5 rounded hover:bg-gray-50 transition shadow-sm flex items-center">
                                            <svg x-show="!showText" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            <svg x-show="showText" style="display: none;" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                                            <span x-text="showText ? 'Hide Original Text' : 'Reveal Original Text'"></span>
                                        </button>
                                    </div>
                                    
                                    <div x-show="showText" style="display: none;" x-transition class="mb-4 bg-white border border-red-100 rounded p-4 shadow-inner">
                                        <p class="text-sm font-semibold text-gray-500 mb-2 uppercase tracking-wide border-b pb-1">Original Customer Input:</p>
                                        <p class="text-gray-900 font-mono text-sm whitespace-pre-wrap">{{ $review->comment }}</p>
                                    </div>

                                    <div class="flex flex-col sm:flex-row justify-between sm:items-center mt-4 pt-3 border-t border-red-200 gap-3">
                                        <p class="text-xs text-gray-700 font-medium">Author: <strong class="text-gray-900">{{ $review->user->name ?? 'Anonymous' }}</strong> (Rated: {{ $review->rating }}★)</p>
                                        
                                        <div class="flex items-center gap-2">
                                            <form action="{{ route('admin.reviews.override', $review) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="bg-white border border-gray-300 text-gray-700 text-xs font-bold hover:bg-gray-50 px-3 py-1.5 rounded shadow-sm transition" onclick="return confirm('Override AI and approve this review for public display?')">
                                                    Approve & Restore
                                                </button>
                                            </form>

                                            <form action="{{ route('reviews.destroy', $review) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="bg-red-600 text-white text-xs font-bold hover:bg-red-700 px-3 py-1.5 rounded shadow-sm transition" onclick="return confirm('Permanently destroy this toxic review?')">
                                                    Delete Permanently
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                            </svg>
                            <p class="text-gray-600">No reviews yet. Be the first to share your thoughts!</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection