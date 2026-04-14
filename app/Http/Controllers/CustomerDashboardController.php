<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;

class CustomerDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $orderStats = [
            'total' => $user->orders()->count(),
            'pending' => $user->orders()->where('status', 'pending')->count(),
            'processing' => $user->orders()->where('status', 'processing')->count(),
            'completed' => $user->orders()->where('status', 'completed')->count(),
            'cancelled' => $user->orders()->where('status', 'cancelled')->count(),
        ];

        $recentOrders = $user->orders()
            ->with('orderItems.book')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Get recently purchased books (from completed orders)
        $recentBooks = $user->orders()
            ->where('status', 'completed')
            ->with('orderItems.book')
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->flatMap(fn ($order) => $order->orderItems->pluck('book'))
            ->unique('id')
            ->take(6);

        $recentReviews = $user->reviews()
            ->with('book')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('dashboard', compact('user', 'orderStats', 'recentOrders', 'recentBooks', 'recentReviews'));
    }
}
