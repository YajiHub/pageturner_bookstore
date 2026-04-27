<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\AuditLog;
use App\Models\DataTransferJob;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::where('role', 'customer')->count(),
            'total_books' => Book::count(),
            'total_categories' => Category::count(),
            'total_orders' => Order::count(),
        ];

        $orderStatusSummary = [
            'pending' => Order::where('status', 'pending')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
        ];

        $recentOrders = Order::with(['user', 'orderItems'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $recentReviews = Review::with(['user', 'book'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $recentTransfers = DataTransferJob::with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        $recentTransfersPayload = $this->buildTransferPayload($recentTransfers);

        $recentAuditLogs = AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $salesMetrics = [
            'today' => [
                'orders' => Order::where('status', 'completed')->whereDate('updated_at', now()->toDateString())->count(),
                'revenue' => (float) Order::where('status', 'completed')->whereDate('updated_at', now()->toDateString())->sum('total_amount'),
            ],
            'last_7_days' => [
                'orders' => Order::where('status', 'completed')->where('updated_at', '>=', now()->subDays(7))->count(),
                'revenue' => (float) Order::where('status', 'completed')->where('updated_at', '>=', now()->subDays(7))->sum('total_amount'),
            ],
            'last_30_days' => [
                'orders' => Order::where('status', 'completed')->where('updated_at', '>=', now()->subDays(30))->count(),
                'revenue' => (float) Order::where('status', 'completed')->where('updated_at', '>=', now()->subDays(30))->sum('total_amount'),
            ],
        ];

        // Make threshold dynamic via env, falling back to 5
        $lowStockThreshold = config('app.low_stock_threshold', env('LOW_STOCK_THRESHOLD', 5));
        
        $lowStockBooks = Book::query()
            ->where('stock_quantity', '<=', $lowStockThreshold)
            ->orderBy('stock_quantity')
            ->take(8)
            ->get(['id', 'title', 'stock_quantity']);

        $transferHealth = [
            'queued' => DataTransferJob::where('status', 'queued')->count(),
            'processing' => DataTransferJob::where('status', 'processing')->count(),
            'completed' => DataTransferJob::where('status', 'completed')->count(),
            'failed' => DataTransferJob::where('status', 'failed')->count(),
            'stalled_processing' => DataTransferJob::where('status', 'processing')
                ->where(function ($query) {
                    $query->where('started_at', '<', now()->subMinutes(90))
                        ->orWhere(function ($nested) {
                            $nested->whereNull('started_at')
                                ->where('created_at', '<', now()->subMinutes(90));
                        });
                })
                ->count(),
        ];

        $topSellingBooks = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('books', 'books.id', '=', 'order_items.book_id')
            ->where('orders.status', 'completed')
            ->select(
                'books.id',
                'books.title',
                DB::raw('SUM(order_items.quantity) as total_units'),
                DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue')
            )
            ->groupBy('books.id', 'books.title')
            ->orderByDesc('total_units')
            ->limit(8)
            ->get();

        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('admin.dashboard', compact(
            'stats',
            'orderStatusSummary',
            'recentOrders',
            'recentReviews',
            'recentTransfers',
            'recentTransfersPayload',
            'recentAuditLogs',
            'categories',
            'salesMetrics',
            'lowStockBooks',
            'lowStockThreshold',
            'transferHealth',
            'topSellingBooks'
        ));
    }

    public function transferJobsProgress(): JsonResponse
    {
        $recentTransfers = DataTransferJob::with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'jobs' => $this->buildTransferPayload($recentTransfers),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    private function buildTransferPayload($transfers): array
    {
        return $transfers->map(fn (DataTransferJob $transfer) => $this->formatTransfer($transfer))->values()->all();
    }

    private function formatTransfer(DataTransferJob $transfer): array
    {
        $progress = $this->deriveProgress($transfer);

        return [
            'id' => $transfer->id,
            'type' => (string) $transfer->type,
            'requested_by' => (string) ($transfer->user?->name ?? 'System'),
            'file' => (string) ($transfer->original_filename ?? '-'),
            'status' => (string) $transfer->status,
            'status_label' => ucfirst((string) $transfer->status),
            'status_class' => $this->statusClass((string) $transfer->status),
            'progress' => $progress,
            'progress_bar_class' => $this->progressBarClass((string) $transfer->status),
            'result_text' => $this->resultText($transfer),
            'result_class' => $transfer->status === 'failed' ? 'text-red-600' : 'text-gray-600',
            'download_url' => $this->downloadUrl($transfer),
            'created_human' => (string) ($transfer->created_at?->diffForHumans() ?? '-'),
        ];
    }

    private function deriveProgress(DataTransferJob $transfer): int
    {
        if ($transfer->status === 'completed' || $transfer->status === 'failed') {
            return 100;
        }

        $stored = (int) ($transfer->progress_percentage ?? 0);
        if ($stored > 0) {
            return min(99, $stored);
        }

        if ($transfer->status === 'queued') {
            return 0;
        }

        if ($transfer->status === 'processing' && (int) $transfer->total_rows > 0) {
            $processed = (int) $transfer->imported_rows + (int) $transfer->failed_rows;
            if ($processed > 0) {
                return min(95, (int) floor(($processed / (int) $transfer->total_rows) * 95));
            }
        }

        return 15;
    }

    private function statusClass(string $status): string
    {
        return match ($status) {
            'queued' => 'bg-gray-100 text-gray-700',
            'processing' => 'bg-blue-100 text-blue-700',
            'completed' => 'bg-green-100 text-green-700',
            'failed' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    private function progressBarClass(string $status): string
    {
        return match ($status) {
            'queued' => 'bg-gray-400',
            'processing' => 'bg-blue-500',
            'completed' => 'bg-green-500',
            'failed' => 'bg-red-500',
            default => 'bg-gray-400',
        };
    }

    private function resultText(DataTransferJob $transfer): string
    {
        if ($transfer->type === 'import' && $transfer->status === 'completed') {
            $message = 'Imported: '.number_format((int) $transfer->imported_rows);
            if ((int) $transfer->failed_rows > 0) {
                $message .= ' | Failed rows: '.number_format((int) $transfer->failed_rows);
            }

            return $message;
        }

        if ($transfer->status === 'failed') {
            return (string) ($transfer->error_message ?: 'Failed to process transfer.');
        }

        if ($transfer->type === 'export' && $transfer->status === 'completed') {
            return 'Ready to download';
        }

        return 'Processing...';
    }

    private function downloadUrl(DataTransferJob $transfer): ?string
    {
        if ($transfer->type !== 'export' || $transfer->status !== 'completed' || ! $transfer->result_path) {
            return null;
        }

        $entity = (string) ($transfer->options['entity'] ?? 'books');

        return match ($entity) {
            'orders' => route('admin.orders.export.download', $transfer),
            'users' => route('admin.users.export.download', $transfer),
            default => route('admin.books.export.download', $transfer),
        };
    }
}   