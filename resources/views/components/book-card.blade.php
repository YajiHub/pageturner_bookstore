@props(['book'])

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-2xl hover:-translate-y-2 transform transition-all duration-300 flex flex-col h-full group">
    <div class="relative h-64 sm:h-72 w-full bg-gray-200 flex flex-col items-center justify-center overflow-hidden">
        @if($book->cover_image)
            <img src="{{ asset('storage/' . $book->cover_image) }}" alt="{{ $book->title }}" class="h-full w-full object-cover group-hover:scale-110 transition-transform duration-700 ease-in-out">
        @else
            <svg class="h-20 w-20 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
            <span class="text-gray-500 font-medium text-sm text-center px-4 leading-tight">{{ $book->title }}</span>
        @endif
        <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm px-2.5 py-1 rounded-full text-sm font-bold text-gray-900 shadow-sm">
            ₱{{ number_format($book->price, 2) }}
        </div>
    </div>

    <div class="p-5 flex flex-col flex-grow">
        <div class="mb-1">
            <span class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">{{ $book->category->name }}</span>
        </div>
        <h3 class="font-bold text-xl text-gray-900 leading-tight line-clamp-2 mb-1 group-hover:text-indigo-700 transition-colors">
            <a href="{{ route('books.show', $book) }}" class="focus:outline-none hover:underline">
                {{ $book->title }}
            </a>
        </h3>
        <p class="text-gray-500 text-sm mb-3">by {{ $book->author }}</p>
        
        <div class="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between relative z-10">
            {{-- Star Rating --}}
            <div class="flex items-center">
                <svg class="h-4 w-4 {{ $book->average_rating >= 1 ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                <span class="ml-1 text-sm font-medium text-gray-700">{{ number_format($book->average_rating ?: 0, 1) }}</span>
                <span class="mx-1 text-gray-400">&middot;</span>
                <span class="text-xs text-gray-500">{{ $book->reviews_count ?? $book->reviews->count() }} reviews</span>
            </div>

            @auth
                @unless(auth()->user()->isAdmin())
                    <form action="{{ route('cart.add', $book) }}" method="POST">
                        @csrf
                        <button type="submit" class="text-indigo-600 bg-indigo-50 hover:bg-indigo-600 hover:text-white p-2 rounded-full transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" title="Add to Cart">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.35 2.7A1 1 0 007 17h10a1 1 0 00.95-.68l1.54-4.32M7 13V6h13v7"></path></svg>
                        </button>
                    </form>
                @endunless
            @else
                <a href="{{ route('login') }}" class="text-indigo-600 bg-indigo-50 hover:bg-indigo-600 hover:text-white p-2 rounded-full transition-colors" title="Login to Add">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.35 2.7A1 1 0 007 17h10a1 1 0 00.95-.68l1.54-4.32M7 13V6h13v7"></path></svg>
                </a>
            @endauth
        </div>
    </div>
</div>
