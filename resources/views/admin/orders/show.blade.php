@extends('layouts.app')

@section('title', 'Order #' . $order->id)

@section('content')
    <div class="mb-6">
        <a href="{{ route('orders.index') }}" class="text-gray-600 hover:text-gray-900">
            &larr; Back to All Orders
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Order Details --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h1 class="text-2xl font-bold">Order #{{ $order->id }}</h1>
                        <p class="text-gray-500">{{ $order->created_at->format('F d, Y \a\t h:i A') }}</p>
                    </div>
                    <span class="inline-block px-3 py-1 rounded-full text-sm font-medium 
                        {{ $order->status === 'completed' ? 'bg-green-100 text-green-800' : 
                           ($order->status === 'cancelled' ? 'bg-red-100 text-red-800' : 
                           ($order->status === 'processing' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800')) }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>

                <div class="border-t pt-6">
                    <h2 class="font-semibold text-lg mb-4">Order Items</h2>
                    
                    <div class="space-y-4">
                        @foreach($order->orderItems as $item)
                            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
                                <div class="h-16 w-12 bg-gray-200 rounded flex-shrink-0 flex items-center justify-center">
                                    @if($item->book->cover_image)
                                        <img src="{{ asset('storage/' . $item->book->cover_image) }}" alt="{{ $item->book->title }}" class="h-full w-full object-cover rounded">
                                    @else
                                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                        </svg>
                                    @endif
                                </div>
                                
                                <div class="flex-grow">
                                    <a href="{{ route('books.show', $item->book) }}" class="font-medium text-gray-800 hover:text-indigo-600">
                                        {{ $item->book->title }}
                                    </a>
                                    <p class="text-sm text-gray-500">by {{ $item->book->author }}</p>
                                    <p class="text-sm text-gray-500">ISBN: {{ $item->book->isbn }}</p>
                                </div>
                                
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
                    <div class="flex justify-end">
                        <div class="text-right">
                            <p class="text-gray-600">Total</p>
                            <p class="text-2xl font-bold text-indigo-600">₱{{ number_format($order->total_amount, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar - Customer Info & Status Update --}}
        <div class="space-y-6">
            {{-- Customer Info --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold text-lg mb-4">Customer Information</h3>
                <div class="space-y-2">
                    <p><span class="text-gray-500">Name:</span> {{ $order->user?->name ?? 'Deleted User' }}</p>
                    <p><span class="text-gray-500">Email:</span> {{ $order->user?->email ?? '-' }}</p>
                    <p><span class="text-gray-500">Customer since:</span> {{ $order->user?->created_at?->format('M d, Y') ?? '-' }}</p>
                </div>
            </div>

            {{-- Update Status --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold text-lg mb-4">Update Status</h3>
                <form action="{{ route('admin.orders.updateStatus', $order) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="mb-4">
                        <select name="status" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700 transition">
                        Update Status
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
