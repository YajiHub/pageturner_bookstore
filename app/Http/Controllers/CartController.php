<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Notifications\NewOrderAdminNotification;
use App\Notifications\OrderPlacedNotification;
use App\Services\AuditLogger;
use App\Services\OrderPlacementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CartController extends Controller
{
    // Show cart contents
    public function index(Request $request)
    {
        $cart = $this->resolveCart($request);
        $books = Book::whereIn('id', array_keys($cart))->get();

        return view('cart.index', compact('books', 'cart'));
    }

    // Add a book to cart
    public function add(Request $request, Book $book)
    {
        // Admin users should not be able to add to cart
        if (Auth::check()) {
            /** @var User $currentUser */
            $currentUser = Auth::user();

            if ($currentUser->isAdmin()) {
                abort(403);
            }
        }

        // Ensure the book has stock available
        if ($book->stock_quantity <= 0) {
            return redirect()->back()->with('error', 'This book is out of stock.');
        }

        $cart = $this->resolveCart($request);
        $currentQty = $cart[$book->id] ?? 0;
        if (($currentQty + 1) > $book->stock_quantity) {
            return redirect()->back()->with('error', 'Not enough stock available to add another copy to your cart.');
        }

        $this->setCartItemQuantity($request, $book->id, $currentQty + 1);

        return redirect()->back()->with('success', 'Book added to cart!');
    }

    // Remove a book from cart
    public function remove(Request $request, Book $book)
    {
        $this->removeCartItem($request, $book->id);

        return redirect()->back()->with('success', 'Book removed from cart!');
    }

    // Update quantity for a cart item
    public function update(Request $request, Book $book)
    {
        // Admin users should not be able to modify cart
        if (Auth::check()) {
            /** @var User $currentUser */
            $currentUser = Auth::user();

            if ($currentUser->isAdmin()) {
                abort(403);
            }
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

        $this->setCartItemQuantity($request, $book->id, $qty);

        return redirect()->back()->with('success', 'Cart updated.');
    }

    // Clear cart
    public function clear(Request $request)
    {
        $this->clearCart($request);

        return redirect()->back()->with('success', 'Cart cleared!');
    }

    public function checkout(Request $request, OrderPlacementService $orderPlacementService)
    {
        $cart = $this->resolveCart($request);

        if ($cart === []) {
            return redirect()->back()->with('error', 'Cart is empty!');
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin()) {
            abort(403);
        }

        if (empty($user->address)) {
            return redirect()->route('profile.edit')->with('error', 'Please add a shipping address to your profile before checking out.');
        }

        try {
            $order = $orderPlacementService->placeOrder($user, $cart, $user->address);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        AuditLogger::log(
            action: 'order.created',
            auditable: $order,
            newValues: $order->only(['user_id', 'address', 'total_amount', 'status']),
            description: 'Order placed from cart checkout.',
            userId: $order->user_id
        );

        $this->clearCart($request);

        $notificationFailed = false;

        try {
            $user->notify(new OrderPlacedNotification($order));
            User::where('role', 'admin')->each(function ($admin) use ($order) {
                $admin->notify(new NewOrderAdminNotification($order));
            });
        } catch (\Throwable $e) {
            report($e);
            $notificationFailed = true;
        }

        $message = $notificationFailed
            ? 'Order placed! Email notifications could not be sent right now.'
            : 'Order placed!';

        return redirect()->route('orders.show', $order)->with('success', $message);
    }

    private function resolveCart(Request $request): array
    {
        if (! Auth::check() || ! Schema::hasTable('carts') || ! Schema::hasTable('cart_items')) {
            return $this->normalizeCartArray($request->session()->get('cart', []));
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            return [];
        }

        $this->mergeSessionCartIntoUserCart($request, $user);

        return $this->getUserCartArray($user);
    }

    private function setCartItemQuantity(Request $request, int $bookId, int $quantity): void
    {
        $quantity = max(1, $quantity);

        if (! Auth::check() || ! Schema::hasTable('carts') || ! Schema::hasTable('cart_items')) {
            $cart = $this->normalizeCartArray($request->session()->get('cart', []));
            $cart[$bookId] = $quantity;
            $request->session()->put('cart', $cart);

            return;
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            return;
        }

        $cart = $this->getOrCreateUserCart($user);
        $item = CartItem::firstOrNew([
            'cart_id' => $cart->id,
            'book_id' => $bookId,
        ]);
        $item->quantity = $quantity;
        $item->save();
    }

    private function removeCartItem(Request $request, int $bookId): void
    {
        if (! Auth::check() || ! Schema::hasTable('carts') || ! Schema::hasTable('cart_items')) {
            $cart = $this->normalizeCartArray($request->session()->get('cart', []));
            unset($cart[$bookId]);
            $request->session()->put('cart', $cart);

            return;
        }

        /** @var User $user */
        $user = Auth::user();

        $cart = Cart::where('user_id', $user->id)->first();
        if (! $cart) {
            return;
        }

        CartItem::where('cart_id', $cart->id)->where('book_id', $bookId)->delete();
    }

    private function clearCart(Request $request): void
    {
        if (Auth::check() && Schema::hasTable('carts') && Schema::hasTable('cart_items')) {
            /** @var User $user */
            $user = Auth::user();
            $cart = Cart::where('user_id', $user->id)->first();

            if ($cart) {
                CartItem::where('cart_id', $cart->id)->delete();
            }
        }

        $request->session()->forget('cart');
    }

    private function mergeSessionCartIntoUserCart(Request $request, User $user): void
    {
        $sessionCart = $this->normalizeCartArray($request->session()->get('cart', []));

        if ($sessionCart === []) {
            return;
        }

        $cart = $this->getOrCreateUserCart($user);

        foreach ($sessionCart as $bookId => $quantity) {
            $book = Book::find($bookId);

            if (! $book || $book->stock_quantity <= 0) {
                continue;
            }

            $item = CartItem::firstOrNew([
                'cart_id' => $cart->id,
                'book_id' => $bookId,
            ]);

            $existingQty = $item->exists ? (int) $item->quantity : 0;
            $mergedQty = min($book->stock_quantity, $existingQty + (int) $quantity);

            if ($mergedQty <= 0) {
                continue;
            }

            $item->quantity = $mergedQty;
            $item->save();
        }

        $request->session()->forget('cart');
    }

    private function getUserCartArray(User $user): array
    {
        $cart = Cart::with('items')->where('user_id', $user->id)->first();

        if (! $cart) {
            return [];
        }

        return $cart->items
            ->pluck('quantity', 'book_id')
            ->map(fn ($qty) => (int) $qty)
            ->all();
    }

    private function getOrCreateUserCart(User $user): Cart
    {
        return Cart::firstOrCreate(['user_id' => $user->id]);
    }

    private function normalizeCartArray(mixed $cart): array
    {
        if (! is_array($cart)) {
            return [];
        }

        return collect($cart)
            ->mapWithKeys(function ($qty, $bookId) {
                return [(int) $bookId => max(1, (int) $qty)];
            })
            ->filter(fn (int $qty, int $bookId) => $bookId > 0 && $qty > 0)
            ->all();
    }
}
