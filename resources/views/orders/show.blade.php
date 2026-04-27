@extends('layouts.app')

@section('title', 'Order #' . $order->id)

@section('content')
    <div class="mb-6">
        <a href="{{ route('orders.index') }}" class="text-gray-600 hover:text-gray-900">
            &larr; Back to My Orders
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-2xl font-bold">Order #{{ $order->id }}</h1>
                <p class="text-gray-500">{{ $order->created_at->format('F d, Y \a\t h:i A') }}</p>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 rounded-full text-sm font-medium 
                    {{ $order->status === 'completed' ? 'bg-green-100 text-green-800' : 
                       ($order->status === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                    {{ ucfirst($order->status) }}
                </span>
            </div>
        </div>

        {{-- Shipping Address --}}
        @if(!empty($order->address))
            <div class="mb-4">
                <h3 class="font-semibold">Shipping Address</h3>
                <p class="text-gray-700">{{ $order->address }}</p>
            </div>
        @endif

        <div class="border-t pt-6">
            <h2 class="font-semibold text-lg mb-4">Order Items</h2>
            
            <div class="space-y-4">
                @foreach($order->orderItems as $item)
                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
                        {{-- Book thumbnail --}}
                        <div class="h-20 w-16 bg-gray-200 rounded flex-shrink-0 flex items-center justify-center">
                            @if($item->book?->cover_image)
                                <img src="{{ asset('storage/' . $item->book->cover_image) }}" alt="{{ $item->book->title }}" class="h-full w-full object-cover rounded">
                            @else
                                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            @endif
                        </div>
                        
                        {{-- Book details --}}
                        <div class="flex-grow">
                            <a href="{{ $item->book ? route('books.show', $item->book) : '#' }}" class="font-medium text-gray-800 hover:text-indigo-600">
                                {{ $item->book?->title ?? 'Deleted Book' }}
                            </a>
                            <p class="text-sm text-gray-500">by {{ $item->book?->author ?? 'Unknown' }}</p>
                            <p class="text-sm text-gray-500">{{ $item->book?->category?->name ?? 'Uncategorized' }}</p>
                        </div>
                        
                        {{-- Price details --}}
                        <div class="text-right">
                            <p class="text-gray-600">₱{{ number_format($item->unit_price, 2) }} × {{ $item->quantity }}</p>
                            <p class="font-bold text-gray-800">₱{{ number_format($item->subtotal, 2) }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Order Total --}}
        <div class="border-t mt-6 pt-6">
            <div class="flex justify-between items-end">
                {{-- Cancel Button (only for pending orders) --}}
                <div>
                    @if($order->status === 'pending')
                        <form action="{{ route('orders.cancel', $order) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
                                Cancel Order
                            </button>
                        </form>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-gray-600">Total</p>
                    <p class="text-2xl font-bold text-indigo-600">₱{{ number_format($order->total_amount, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Review CTA (only for completed orders) --}}
    @if($order->status === 'completed')
        <div class="mt-6 bg-indigo-50 rounded-lg p-6">
            <h3 class="font-semibold text-lg mb-2">Enjoyed your purchase?</h3>
            <p class="text-gray-600 mb-4">Leave a review to help other readers discover great books!</p>
            <div class="flex flex-wrap gap-2">
                @foreach($order->orderItems as $item)
                    <a href="{{ route('books.show', $item->book) }}#reviews" 
                       class="inline-block bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition text-sm">
                        Review "{{ \Illuminate\Support\Str::limit($item->book?->title ?? 'Book', 20) }}"
                    </a>
                @endforeach
            </div>
        </div>
    @elseif($order->status === 'pending')
        <div class="mt-6 bg-yellow-50 rounded-lg p-6">
            <h3 class="font-semibold text-lg mb-2">Order Pending</h3>
            <p class="text-gray-600">Your order is awaiting processing. You'll be able to leave reviews once it's completed.</p>
        </div>
    @elseif($order->status === 'processing')
        <div class="mt-6 bg-blue-50 rounded-lg p-6">
            <h3 class="font-semibold text-lg mb-2">Order Processing</h3>
            <p class="text-gray-600">Your order is being processed. You'll be able to leave reviews once it's completed.</p>
        </div>
    @endif
@endsection
