@extends('layouts.app')

@section('title', 'My Dashboard')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Dashboard</h2>
@endsection

@section('content')
    <!-- Welcome Message -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h3 class="text-2xl font-bold text-gray-800">Welcome back, {{ $user->name }}!</h3>
        <p class="text-gray-600 mt-1">Here's a summary of your account activity.</p>

        <!-- Account Status -->
        <div class="flex flex-wrap gap-3 mt-4">
            @if($user->hasVerifiedEmail())
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                    Email Verified
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    Email Not Verified
                </span>
            @endif

            @if($user->two_factor_enabled)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                    2FA Enabled
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    2FA Disabled
                </span>
            @endif
        </div>
    </div>

    <!-- Order Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $orderStats['total'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Total Orders</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-2xl font-bold text-yellow-600">{{ $orderStats['pending'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Pending</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $orderStats['processing'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Processing</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ $orderStats['completed'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Completed</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-2xl font-bold text-red-600">{{ $orderStats['cancelled'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Cancelled</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Recent Orders -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Recent Orders</h3>
                <a href="{{ route('orders.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">View All →</a>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($recentOrders as $order)
                    <a href="{{ route('orders.show', $order) }}" class="block p-4 hover:bg-gray-50 transition">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-medium text-gray-800">Order #{{ $order->id }}</p>
                                <p class="text-sm text-gray-500">{{ $order->created_at->format('M d, Y') }} · {{ $order->orderItems->count() }} item(s)</p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium text-gray-800">${{ number_format($order->total_amount, 2) }}</p>
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'processing' => 'bg-blue-100 text-blue-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                    ];
                                @endphp
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $statusColors[$order->status] ?? 'bg-gray-100' }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-6 text-center text-gray-500">
                        <p>No orders yet.</p>
                        <a href="{{ route('books.index') }}" class="text-indigo-600 hover:underline text-sm mt-1 inline-block">Browse books →</a>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- My Reviews -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold text-gray-800">My Reviews</h3>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($recentReviews as $review)
                    <div class="p-4">
                        <div class="flex justify-between items-start">
                            <a href="{{ route('books.show', $review->book) }}" class="font-medium text-indigo-600 hover:underline">{{ $review->book->title }}</a>
                            <div class="flex items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-3 h-3 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                @endfor
                            </div>
                        </div>
                        @if($review->comment)
                            <p class="mt-1 text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($review->comment, 100) }}</p>
                        @endif
                        <p class="mt-1 text-xs text-gray-400">{{ $review->created_at->diffForHumans() }}</p>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-500">No reviews yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recently Purchased Books -->
    @if($recentBooks->isNotEmpty())
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Recently Purchased Books</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach($recentBooks as $book)
                    <a href="{{ route('books.show', $book) }}" class="group">
                        <div class="aspect-[2/3] bg-gray-200 rounded-lg overflow-hidden mb-2">
                            @if($book->cover_image)
                                <img src="{{ asset('storage/' . $book->cover_image) }}" alt="{{ $book->title }}" class="w-full h-full object-cover group-hover:scale-105 transition">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                </div>
                            @endif
                        </div>
                        <p class="text-sm font-medium text-gray-800 truncate group-hover:text-indigo-600">{{ $book->title }}</p>
                        <p class="text-xs text-gray-500">{{ $book->author }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Quick Links -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Links</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <a href="{{ route('books.index') }}" class="flex items-center p-3 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                <span class="text-sm font-medium text-indigo-700">Browse Books</span>
            </a>
            <a href="{{ route('orders.index') }}" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition">
                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                <span class="text-sm font-medium text-green-700">Order History</span>
            </a>
            <a href="{{ route('profile.edit') }}" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span class="text-sm font-medium text-purple-700">Manage Profile</span>
            </a>
            <a href="{{ route('cart.index') }}" class="flex items-center p-3 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                <svg class="w-5 h-5 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path></svg>
                <span class="text-sm font-medium text-orange-700">View Cart</span>
            </a>
        </div>
    </div>
@endsection
