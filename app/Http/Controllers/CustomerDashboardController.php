<?php

namespace App\Http\Controllers;

use App\Exports\CustomerOrdersExport;
use App\Exports\ReadingHistoryExport;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class CustomerDashboardController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

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

    public function exportData(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $relations = ['orders.orderItems.book', 'reviews.book'];

        if (Schema::hasTable('reading_histories')) {
            $relations[] = 'readingHistories.book';
            $relations[] = 'readingHistories.order';
        }

        $user->load($relations);

        $payload = [
            'generated_at' => now()->toIso8601String(),
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'address' => $user->address,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
                'updated_at' => $user->updated_at?->toIso8601String(),
                'two_factor_enabled' => $user->two_factor_enabled,
            ],
            'orders' => $user->orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'address' => $order->address,
                    'total_amount' => $order->total_amount,
                    'created_at' => $order->created_at?->toIso8601String(),
                    'updated_at' => $order->updated_at?->toIso8601String(),
                    'items' => $order->orderItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'book_id' => $item->book_id,
                            'book_title' => $item->book?->title,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'subtotal' => $item->subtotal,
                        ];
                    })->values(),
                ];
            })->values(),
            'reviews' => $user->reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'book_id' => $review->book_id,
                    'book_title' => $review->book?->title,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at?->toIso8601String(),
                    'updated_at' => $review->updated_at?->toIso8601String(),
                ];
            })->values(),
            'reading_history' => Schema::hasTable('reading_histories')
                ? $user->readingHistories->map(function ($entry) {
                    return [
                        'id' => $entry->id,
                        'event_type' => $entry->event_type,
                        'book_id' => $entry->book_id,
                        'book_title' => $entry->book?->title,
                        'order_id' => $entry->order_id,
                        'quantity' => $entry->quantity,
                        'last_seen_at' => $entry->last_seen_at?->toIso8601String(),
                        'created_at' => $entry->created_at?->toIso8601String(),
                        'updated_at' => $entry->updated_at?->toIso8601String(),
                    ];
                })->values()
                : [],
        ];

        $filename = 'pageturner-my-data-'.now()->format('Y_m_d_His').'.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function exportOrders(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user || $user->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'format' => 'nullable|in:pdf,xlsx',
        ]);

        $format = $validated['format'] ?? 'xlsx';
        $orders = $user->orders()->with('orderItems.book')->orderByDesc('created_at')->get();
        $filename = 'pageturner-order-history-'.now()->format('Y_m_d_His').'.'.$format;

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('dashboard.exports.orders', [
                'orders' => $orders,
            ]);

            return response()->streamDownload(fn () => print($pdf->output()), $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        return Excel::download(new CustomerOrdersExport($user->id), $filename, ExcelWriter::XLSX);
    }

    public function exportReadingHistory(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user || $user->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'format' => 'nullable|in:pdf,xlsx',
        ]);

        $format = $validated['format'] ?? 'xlsx';
        if (! Schema::hasTable('reading_histories')) {
            return redirect()->route('dashboard')->with('error', 'Reading history export is not available until the reading history migration has been applied.');
        }

        $history = ReadingHistory::query()
            ->where('user_id', $user->id)
            ->with(['book', 'order'])
            ->orderByDesc('last_seen_at')
            ->orderByDesc('created_at')
            ->get();
        $filename = 'pageturner-reading-history-'.now()->format('Y_m_d_His').'.'.$format;

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('dashboard.exports.reading-history', [
                'history' => $history,
            ]);

            return response()->streamDownload(fn () => print($pdf->output()), $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        return Excel::download(new ReadingHistoryExport($user->id), $filename, ExcelWriter::XLSX);
    }
}
