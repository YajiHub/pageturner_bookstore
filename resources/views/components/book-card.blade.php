@props(['book'])

@php
    // FIX: Only calculate average rating based on valid, non-toxic reviews!
    $cleanReviews = $book->reviews->where('is_flagged_by_ai', false);
    $rating = $cleanReviews->count() > 0 ? round($cleanReviews->avg('rating'), 1) : 0;
@endphp

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-200 flex flex-col h-full">
    <a href="{{ route('books.show', $book) }}" class="h-64 w-full bg-gray-100 relative overflow-hidden flex items-center justify-center group block">
        @if($book->cover_image)
            <img src="{{ asset('storage/' . $book->cover_image) }}" alt="Cover of {{ $book->title }}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
        @else
            <span class="text-gray-400 text-sm font-medium">No Cover</span>
        @endif
    </a>
    
    <div class="p-4 flex flex-col flex-grow">
        <div class="flex justify-between items-start mb-1">
            <a href="{{ route('books.show', $book) }}" class="block flex-1 pr-2">
                <h4 class="font-bold text-lg text-gray-900 leading-tight truncate hover:text-indigo-600 transition-colors" title="{{ $book->title }}">
                    {{ $book->title }}
                </h4>
            </a>
            <div class="flex items-center text-sm bg-yellow-50 px-2 py-0.5 rounded-full border border-yellow-100 shadow-sm flex-shrink-0">
                <span class="text-yellow-500 font-black mr-1">★</span>
                <span class="font-bold text-yellow-700">{{ $rating > 0 ? number_format($rating, 1) : 'New' }}</span>
            </div>
        </div>
        
        <p class="text-sm text-gray-600 mb-3 truncate">By {{ $book->author }}</p>
        
        <div class="mt-auto flex justify-between items-center pt-3 border-t border-gray-100">
            <span class="text-xl font-black text-indigo-600">₱{{ number_format($book->price, 2) }}</span>
            <a href="{{ route('books.show', $book) }}" class="text-xs font-bold text-white bg-gray-900 hover:bg-indigo-600 px-4 py-2 rounded shadow-sm transition-colors">
                View Details
            </a>
        </div>
    </div>
</div>