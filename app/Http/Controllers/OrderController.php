<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\NewOrderAdminNotification;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderController extends Controller
{
    /**
     * Display orders - all for admin, own for customers.
     */
    public function index()
    {
        if (auth()->user()->isAdmin()) {
            // Admin sees all customer orders
            $orders = Order::with(['orderItems.book', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);
            
            return view('admin.orders.index', compact('orders'));
        }
        
        // Customer sees only their orders
        $orders = auth()->user()->orders()
            ->with('orderItems.book')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('orders.index', compact('orders'));
    }

    /**
     * Store a new order (add book to orders).
     */
    public function store(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'quantity' => 'integer|min:1|max:10',
        ]);
        // Admin users should not be creating customer orders
        if (auth()->check() && auth()->user()->isAdmin()) {
            abort(403);
        }

        $book = Book::findOrFail($request->book_id);
        $quantity = $request->quantity ?? 1;

        if ($book->stock_quantity <= 0) {
            return back()->with('error', 'This book is out of stock.');
        }

        if ($quantity > $book->stock_quantity) {
            return back()->with('error', 'Requested quantity exceeds available stock.');
        }

        // Create the order and item (order remains pending; stock will be decremented when admin marks completed)
        // Ensure user has address
        $user = auth()->user();
        if (empty($user->address)) {
            return back()->with('error', 'Please add a shipping address in your profile page before placing an order.');
        }

        $order = null;
        DB::transaction(function () use ($book, $quantity, $user, &$order) {
            $order = Order::create([
                'user_id' => auth()->id(),
                'total_amount' => $book->price * $quantity,
                'status' => 'pending',
                'address' => $user->address,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'book_id' => $book->id,
                'quantity' => $quantity,
                'unit_price' => $book->price,
            ]);
        });

        if (!$order) {
            return back()->with('error', 'Unable to place order right now. Please try again.');
        }

        $notificationFailed = false;

        // Send notifications (non-blocking for core order creation)
        try {
            $user->notify(new OrderPlacedNotification($order));
            User::where('role', 'admin')->each(function ($admin) use ($order) {
                $admin->notify(new NewOrderAdminNotification($order));
            });
        } catch (Throwable $e) {
            report($e);
            $notificationFailed = true;
        }

        $message = $notificationFailed
            ? 'Order placed successfully, but we could not send email notifications right now.'
            : 'Order placed successfully!';

        return back()->with('success', $message);
    }

    /**
     * Display a specific order.
     */
    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load(['orderItems.book', 'user']);
        
        // Use admin view if admin
        if (auth()->user()->isAdmin()) {
            return view('admin.orders.show', compact('order'));
        }

        return view('orders.show', compact('order'));
    }

    /**
     * Update order status (admin only).
     */
    public function updateStatus(Request $request, Order $order)
    {
        $this->authorize('updateStatus', $order);

        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
        ]);
        $oldStatus = $order->status;
        $newStatus = $request->status;

        DB::transaction(function () use ($order, $oldStatus, $newStatus) {
            // Refresh order items
            $order->load('orderItems.book');

            // If changing to completed from a non-completed state, decrement stock
            if ($oldStatus !== 'completed' && $newStatus === 'completed') {
                foreach ($order->orderItems as $item) {
                    $book = Book::lockForUpdate()->find($item->book_id);
                    if ($book) {
                        $book->stock_quantity = max(0, $book->stock_quantity - $item->quantity);
                        $book->save();
                    }
                }
            }

            // If reverting from completed to non-completed, restore stock
            if ($oldStatus === 'completed' && $newStatus !== 'completed') {
                foreach ($order->orderItems as $item) {
                    $book = Book::lockForUpdate()->find($item->book_id);
                    if ($book) {
                        $book->stock_quantity = $book->stock_quantity + $item->quantity;
                        $book->save();
                    }
                }
            }

            $order->update(['status' => $newStatus]);
        });

        $notificationFailed = false;

        // Notify the customer about status change (non-blocking)
        try {
            if ($order->user) {
                $order->user->notify(new OrderStatusChangedNotification($order, $oldStatus, $newStatus));
            }
        } catch (Throwable $e) {
            report($e);
            $notificationFailed = true;
        }

        $message = 'Order status updated to ' . ucfirst($request->status);
        if ($notificationFailed) {
            $message .= ', but notification email could not be sent.';
        }

        return back()->with('success', $message);
    }

    /**
     * Cancel an order (customer only, pending orders only).
     */
    public function cancel(Order $order)
    {
        $this->authorize('cancel', $order);

        $order->update(['status' => 'cancelled']);

        return redirect()->route('orders.index')->with('success', 'Order cancelled successfully.');
    }
}
