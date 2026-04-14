@extends('layouts.app')

@section('title', 'Shopping Cart')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Shopping Cart</h1>
    @if(count($books) > 0)
        <table class="w-full mb-6">
            <thead>
                <tr>
                    <th class="text-left">Title</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php $total = 0; @endphp
                @foreach($books as $book)
                    <tr>
                        <td class="align-middle">{{ $book->title }}</td>
                        <td>
                            @if($book->stock_quantity > 0)
                                <form action="{{ route('cart.update', $book) }}" method="POST" class="flex items-center gap-2">
                                    @csrf
                                    <input type="number" name="quantity" value="{{ $cart[$book->id] }}" min="1" max="{{ $book->stock_quantity }}" class="w-20 border rounded px-2 py-1">
                                    <button type="submit" class="bg-gray-300 px-2 py-1 rounded">Update</button>
                                </form>
                                <div class="text-xs text-gray-500">In stock: {{ $book->stock_quantity }}</div>
                            @else
                                <div class="text-red-600">Out of Stock</div>
                            @endif
                        </td>
                        <td class="align-middle">${{ number_format($book->price, 2) }}</td>
                        <td class="align-middle">${{ number_format($book->price * $cart[$book->id], 2) }}</td>
                        <td class="align-middle">
                            <form action="{{ route('cart.remove', $book) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-red-600">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @php $total += $book->price * $cart[$book->id]; @endphp
                @endforeach
            </tbody>
        </table>
        <div class="mb-6 font-bold text-xl">Total: ${{ number_format($total, 2) }}</div>
        <form action="{{ route('cart.clear') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="bg-gray-300 px-4 py-2 rounded">Clear Cart</button>
        </form>
        <form action="{{ route('cart.checkout') }}" method="POST" class="inline ml-4">
            @csrf
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Checkout</button>
        </form>
    @else
        <p>Your cart is empty.</p>
    @endif
</div>
@endsection
