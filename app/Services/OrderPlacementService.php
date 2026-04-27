<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderPlacementService
{
    public function placeOrder(User $user, array $items, string $address): Order
    {
        $normalizedItems = collect($items)
            ->mapWithKeys(function ($quantity, $bookId) {
                return [(int) $bookId => max(0, (int) $quantity)];
            })
            ->filter(fn (int $quantity) => $quantity > 0)
            ->all();

        if ($normalizedItems === []) {
            throw new RuntimeException('Cart is empty.');
        }

        return DB::transaction(function () use ($user, $normalizedItems, $address) {
            $books = Book::query()
                ->whereIn('id', array_keys($normalizedItems))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($books->count() !== count($normalizedItems)) {
                throw new RuntimeException('One or more books in your cart are no longer available.');
            }

            $total = 0;

            foreach ($normalizedItems as $bookId => $quantity) {
                $book = $books->get($bookId);

                if (! $book || $book->stock_quantity < $quantity) {
                    $bookTitle = $book ? $book->title : 'selected book';

                    throw new RuntimeException("Requested quantity for {$bookTitle} exceeds available stock.");
                }

                $total += $book->price * $quantity;
            }

            $order = Order::create([
                'user_id' => $user->id,
                'address' => $address,
                'total_amount' => $total,
                'status' => 'pending',
            ]);

            foreach ($normalizedItems as $bookId => $quantity) {
                $book = $books->get($bookId);

                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id' => $book->id,
                    'quantity' => $quantity,
                    'unit_price' => $book->price,
                ]);

                $book->decrement('stock_quantity', $quantity);

                ReadingHistory::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'order_id' => $order->id,
                    'event_type' => 'purchased',
                    'quantity' => $quantity,
                    'last_seen_at' => now(),
                ]);
            }

            return $order->load(['orderItems.book', 'user']);
        });
    }
}