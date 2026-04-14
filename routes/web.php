<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');

// Cart routes
Route::get('/cart', [App\Http\Controllers\CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{book}', [App\Http\Controllers\CartController::class, 'add'])->name('cart.add');
Route::post('/cart/remove/{book}', [App\Http\Controllers\CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/update/{book}', [App\Http\Controllers\CartController::class, 'update'])->name('cart.update');
Route::post('/cart/clear', [App\Http\Controllers\CartController::class, 'clear'])->name('cart.clear');
Route::post('/cart/checkout', function (Illuminate\Http\Request $request) {
    $cart = $request->session()->get('cart', []);
    if (empty($cart)) {
        return redirect()->back()->with('error', 'Cart is empty!');
    }
    // checkout creates an order for all cart items
    $user = auth()->user();
    if (!$user) {
        return redirect()->route('login');
    }

    // Admin users may not checkout
    if ($user->isAdmin()) {
        abort(403);
    }

    // Ensure user has address on file
    if (empty($user->address)) {
        return redirect()->route('profile.edit')->with('error', 'Please add a shipping address to your profile before checking out.');
    }

    $total = 0;
    foreach ($cart as $bookId => $qty) {
        $book = App\Models\Book::find($bookId);
        if ($book) {
            // Prevent checkout if stock is insufficient or zero
            if ($book->stock_quantity <= 0) {
                return redirect()->back()->with('error', "{$book->title} is out of stock and cannot be purchased.");
            }
            if ($qty > $book->stock_quantity) {
                return redirect()->back()->with('error', "Requested quantity for {$book->title} exceeds available stock.");
            }
            $total += $book->price * $qty;
        }
    }

    // Create order and items (order remains pending; stock will change when admin marks completed)
    $order = App\Models\Order::create([
        'user_id' => $user->id,
        'total_amount' => $total,
        'status' => 'pending',
        'address' => $user->address,
    ]);
    foreach ($cart as $bookId => $qty) {
        $book = App\Models\Book::find($bookId);
        if ($book) {
            App\Models\OrderItem::create([
                'order_id' => $order->id,
                'book_id' => $book->id,
                'quantity' => $qty,
                'unit_price' => $book->price,
            ]);
        }
    }

    $request->session()->forget('cart');

    $notificationFailed = false;

    // Send notifications (non-blocking for successful checkout)
    try {
        $user->notify(new \App\Notifications\OrderPlacedNotification($order));
        \App\Models\User::where('role', 'admin')->each(function ($admin) use ($order) {
            $admin->notify(new \App\Notifications\NewOrderAdminNotification($order));
        });
    } catch (\Throwable $e) {
        report($e);
        $notificationFailed = true;
    }

    $message = $notificationFailed
        ? 'Order placed! Email notifications could not be sent right now.'
        : 'Order placed!';

    return redirect()->route('orders.show', $order)->with('success', $message);
})->middleware(['auth', 'verified'])->name('cart.checkout');

// Book browsing (public)
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

// Category browsing (public)
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

// Authenticated routes (email verified required)
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard - redirect based on role
    Route::get('/dashboard', function () {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        return app(CustomerDashboardController::class)->index();
    })->name('dashboard');

    // Review routes
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Order routes
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
});

// Profile routes (auth only, no email verification needed so users can verify from here)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin-only routes (Category & Book management)
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Category management
    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Book management
    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');

    // Order management (admin)
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
});

require __DIR__.'/auth.php';
