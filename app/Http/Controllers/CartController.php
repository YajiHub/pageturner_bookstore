<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // Show cart contents
    public function index(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        $books = Book::whereIn('id', array_keys($cart))->get();
        return view('cart.index', compact('books', 'cart'));
    }

    // Add a book to cart
    public function add(Request $request, Book $book)
    {
        // Admin users should not be able to add to cart
        if (auth()->check() && auth()->user()->isAdmin()) {
            abort(403);
        }

        // Ensure the book has stock available
        if ($book->stock_quantity <= 0) {
            return redirect()->back()->with('error', 'This book is out of stock.');
        }

        $cart = $request->session()->get('cart', []);
        $currentQty = $cart[$book->id] ?? 0;
        if (($currentQty + 1) > $book->stock_quantity) {
            return redirect()->back()->with('error', 'Not enough stock available to add another copy to your cart.');
        }

        $cart[$book->id] = $currentQty + 1;
        $request->session()->put('cart', $cart);
        return redirect()->back()->with('success', 'Book added to cart!');
    }

    // Remove a book from cart
    public function remove(Request $request, Book $book)
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$book->id]);
        $request->session()->put('cart', $cart);
        return redirect()->back()->with('success', 'Book removed from cart!');
    }

    // Update quantity for a cart item
    public function update(Request $request, Book $book)
    {
        // Admin users should not be able to modify cart
        if (auth()->check() && auth()->user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $qty = (int) $request->quantity;

        // Check stock availability
        if ($book->stock_quantity <= 0) {
            return redirect()->back()->with('error', 'This book is out of stock.');
        }

        if ($qty > $book->stock_quantity) {
            return redirect()->back()->with('error', 'Requested quantity exceeds available stock.');
        }

        $cart = $request->session()->get('cart', []);
        $cart[$book->id] = $qty;
        $request->session()->put('cart', $cart);

        return redirect()->back()->with('success', 'Cart updated.');
    }

    // Clear cart
    public function clear(Request $request)
    {
        $request->session()->forget('cart');
        return redirect()->back()->with('success', 'Cart cleared!');
    }
}
