@extends('layouts.app')
@section('title', "My Orders")

@section('content')
    <h1 class="text-3xl font-bold mb-6">My Orders</h1>

    @if($orders->isEmpty())
        <x-alert type="info">
            You haven't placed any orders yet. Start browsing our collection and find your next great read!
        </x-alert>
    @else
        <div class="space-y-6">
            @foreach($orders as $order)
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <a href="{{ route('orders.show', $order) }}" class="text-xl font-semibold hover:text-indigo-600">
                                Order #{{ $order->id }}
                            </a>
                            <p class="text-gray-500 text-sm">{{ $order->created_at->format('M d, Y \a\t h:i A') }}</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-medium 
                                {{ $order->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ ucfirst($order->status) }}
                            </span>
                            <p class="text-lg font-bold text-gray-800 mt-1">₱{{ number_format($order->total_amount, 2) }}</p>
                            <a href="{{ route('orders.show', $order) }}" class="text-sm text-indigo-600 hover:underline">View Details →</a>
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <h3 class="font-medium text-gray-700 mb-3">Items:</h3>
                        <div class="space-y-3">
                            @foreach($order->orderItems as $item)
                                <div class="flex items-center justify-between bg-gray-50 p-3 rounded">
                                    <div class="flex items-center gap-4">
                                        <div class="h-12 w-10 bg-gray-200 rounded flex items-center justify-center">
                                            @if($item->book->cover_image)
                                                <img src="{{ asset('storage/' . $item->book->cover_image) }}" alt="{{ $item->book->title }}" class="h-full w-full object-cover rounded">
                                            @else
                                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <a href="{{ route('books.show', $item->book) }}" class="font-medium text-gray-800 hover:text-indigo-600">
                                                {{ $item->book->title }}
                                            </a>
                                            <p class="text-sm text-gray-500">by {{ $item->book->author }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-gray-600">Qty: {{ $item->quantity }}</p>
                                        <p class="font-medium">₱{{ number_format($item->subtotal, 2) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- spacer to push footer down --}}
    <div class="h-32"></div>    
@endsection
