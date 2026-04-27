@extends('layouts.app')

@section('title', 'Manage Orders')

@section('content')
    <h1 class="text-3xl font-bold mb-6">Customer Orders</h1>

    @if($orders->isEmpty())
        <x-alert type="info">
            No orders have been placed yet.
        </x-alert>
    @else
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($orders as $order)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('orders.show', $order) }}" class="text-indigo-600 hover:underline font-medium">
                                    #{{ $order->id }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $order->user?->name ?? 'Deleted User' }}</div>
                                <div class="text-sm text-gray-500">{{ $order->user?->email ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $order->orderItems->count() }} item(s)
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                ₱{{ number_format($order->total_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-block px-2 py-1 rounded-full text-xs font-medium 
                                    {{ $order->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                       ($order->status === 'cancelled' ? 'bg-red-100 text-red-800' : 
                                       ($order->status === 'processing' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800')) }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $order->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('orders.show', $order) }}" class="text-indigo-600 hover:underline">
                                    View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @endif
@endsection
