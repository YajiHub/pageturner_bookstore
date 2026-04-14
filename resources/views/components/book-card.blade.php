@props(['book'])

<div class="bg-gray-300 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
    <div class="h-48 bg-gray-200 flex items-center justify-center">
        @if($book->cover_image)
            <img src="{{ asset('storage/' . $book->cover_image) }}" alt="{{ $book->title }}" class="h-full w-full object-cover">
        @else
            <svg class="h-20 w-20 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
        @endif
    </div>
    <div class="p-4">
        <h3 class="font-bold text-lg text-gray-900 truncate">{{ $book->title }}</h3>
        <p class="text-gray-600 text-sm">by {{ $book->author }}</p>
        {{-- genre --}}
        <p class="text-gray-400 text-sm mt-1 italic">{{ $book->category->name }}</p> 
        <p class="text-gray-700 font-bold mt-2">${{ number_format($book->price, 2) }}</p>

        {{-- Star Rating --}}
        <div class="flex items-center mt-2">
            @for($i = 1; $i <= 5; $i++)
                @if($i <= round($book->average_rating))
                    <svg class="h-4 w-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                @else
                    <svg class="h-4 w-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                @endif
            @endfor
            <span class="ml-2 mr-3 text-sm text-gray-500" style="padding-right: 1em">({{ $book->reviews->count() }})</span>

            @auth
                @unless(auth()->user()->isAdmin())
                    <div class="ml-auto flex items-center gap-2">
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
                    </div>
                @endunless
            @else
                <a href="{{ route('login') }}" class="ml-auto bg-gray-800 hover:bg-gray-700 text-white px-3 py-2 rounded-md text-sm">Add to Cart</a>
            @endauth
        </div>

        <a href="{{ route('books.show', $book) }}" class="mt-4 block text-center bg-gray-600 text-white py-2 rounded hover:bg-gray-400 transition">
            View Details
        </a>
    </div>
</div>
