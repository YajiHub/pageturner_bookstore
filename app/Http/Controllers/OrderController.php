<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Exports\OrdersExport;
use App\Jobs\ProcessOrdersExportJob;
use App\Models\DataTransferJob;
use App\Models\ExportLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\NewOrderAdminNotification;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderStatusChangedNotification;
use App\Services\AuditLogger;
use App\Services\OrderPlacementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class OrderController extends Controller
{
    /**
     * Display orders - all for admin, own for customers.
     */
    public function index()
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($currentUser->isAdmin()) {
            // Admin sees all customer orders
            $orders = Order::with(['orderItems.book', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return view('admin.orders.index', compact('orders'));
        }

        // Customer sees only their orders
        $orders = $currentUser->orders()
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
        if (Auth::check()) {
            /** @var User $currentUser */
            $currentUser = Auth::user();

            if ($currentUser->isAdmin()) {
                abort(403);
            }
        }

        /** @var User $user */
        $user = Auth::user();
        if (empty($user->address)) {
            return back()->with('error', 'Please add a shipping address in your profile page before placing an order.');
        }

        try {
            $order = app(OrderPlacementService::class)->placeOrder($user, [
                (int) $request->book_id => (int) ($request->quantity ?? 1),
            ], $user->address);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditLogger::log(
            action: 'order.created',
            auditable: $order,
            newValues: $order->only(['user_id', 'address', 'total_amount', 'status']),
            description: 'Order placed by customer.',
            userId: $order->user_id
        );

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
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($currentUser->isAdmin()) {
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

            // Inventory is reserved when the order is placed, so only cancellations restore stock.
            if ($oldStatus !== 'cancelled' && $newStatus === 'cancelled') {
                foreach ($order->orderItems as $item) {
                    $book = Book::lockForUpdate()->find($item->book_id);
                    if ($book) {
                        $book->stock_quantity += $item->quantity;
                        $book->save();
                    }
                }
            }

            // Reopening a cancelled order must reserve stock again.
            if ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
                foreach ($order->orderItems as $item) {
                    $book = Book::lockForUpdate()->find($item->book_id);
                    if ($book) {
                        if ($book->stock_quantity < $item->quantity) {
                            throw new \RuntimeException("Not enough stock to reopen order item for {$book->title}.");
                        }

                        $book->stock_quantity -= $item->quantity;
                        $book->save();
                    }
                }
            }

            $order->update(['status' => $newStatus]);
        });

        AuditLogger::log(
            action: 'order.status_updated',
            auditable: $order,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $newStatus],
            description: 'Order status changed by admin.'
        );

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

        $message = 'Order status updated to '.ucfirst($request->status);
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

        $oldStatus = $order->status;

        DB::transaction(function () use ($order, $oldStatus) {
            $order->load('orderItems.book');

            if ($oldStatus !== 'cancelled') {
                foreach ($order->orderItems as $item) {
                    $book = Book::lockForUpdate()->find($item->book_id);
                    if ($book) {
                        $book->stock_quantity += $item->quantity;
                        $book->save();
                    }
                }
            }

            $order->update(['status' => 'cancelled']);
        });

        AuditLogger::log(
            action: 'order.cancelled',
            auditable: $order,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => 'cancelled'],
            description: 'Order cancelled by customer.',
            userId: $order->user_id
        );

        return redirect()->route('orders.index')->with('success', 'Order cancelled successfully.');
    }

    public function export(Request $request)
    {
        abort_unless(Auth::check() && Auth::user()->isAdmin(), 403);

        $validated = $request->validate([
            'format' => 'nullable|in:xlsx,csv,pdf',
            'columns' => 'nullable|array',
            'columns.*' => 'string',
            'status' => 'nullable|in:pending,processing,completed,cancelled',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'search' => 'nullable|string|max:255',
        ]);

        $format = $validated['format'] ?? 'xlsx';
        $columns = OrdersExport::normalizeColumns($validated['columns'] ?? []);
        $filters = [
            'status' => $validated['status'] ?? null,
            'from_date' => $validated['from_date'] ?? null,
            'to_date' => $validated['to_date'] ?? null,
            'search' => $validated['search'] ?? null,
        ];

        $exportQuery = (new OrdersExport($filters, $columns))->query();
        $totalRows = (clone $exportQuery)->count();
        $filename = 'orders_export_'.now()->format('Y_m_d_His').'.'.$format;

        $transfer = DataTransferJob::create([
            'user_id' => Auth::id(),
            'type' => 'export',
            'status' => $totalRows > 10000 ? 'queued' : 'processing',
            'progress_percentage' => $totalRows > 10000 ? 0 : 90,
            'original_filename' => $filename,
            'options' => [
                'entity' => 'orders',
                'format' => $format,
                'filters' => $filters,
                'columns' => $columns,
            ],
            'total_rows' => $totalRows,
        ]);

        $exportLog = ExportLog::create([
            'user_id' => Auth::id(),
            'data_transfer_job_id' => $transfer->id,
            'format' => $format,
            'filters' => $filters,
            'selected_columns' => $columns,
            'status' => $totalRows > 10000 ? 'queued' : 'processing',
        ]);

        AuditLogger::log(
            action: 'transfer.orders_export.queued',
            auditable: $transfer,
            newValues: [
                'original_filename' => $transfer->original_filename,
                'format' => $format,
                'total_rows' => $totalRows,
                'filters' => $filters,
                'selected_columns' => $columns,
            ],
            description: 'Orders export queued by admin.'
        );

        if ($totalRows > 10000) {
            ProcessOrdersExportJob::dispatch($transfer->id, $filters, $columns, $format, $exportLog->id);

            return redirect()->route('admin.dashboard')->with('success', 'Orders export queued. Refresh Data Transfer Jobs to download when ready.');
        }

        $resultPath = 'exports/'.$filename;

        try {
            $this->storeOrdersExportFile($format, $resultPath, $filters, $columns);

            $transfer->update([
                'status' => 'completed',
                'result_path' => $resultPath,
                'finished_at' => now(),
                'progress_percentage' => 100,
            ]);

            $exportLog->update([
                'status' => 'completed',
                'download_link' => route('admin.orders.export.download', $transfer),
                'rows_exported' => $totalRows,
            ]);

            AuditLogger::log(
                action: 'transfer.orders_export.completed',
                auditable: $transfer,
                newValues: ['result_path' => $resultPath],
                description: 'Orders export completed synchronously by admin.'
            );

            return response()->download(Storage::disk('local')->path($resultPath), basename($resultPath));
        } catch (\Throwable $e) {
            $transfer->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
                'progress_percentage' => 100,
            ]);

            $exportLog->update([
                'status' => 'failed',
            ]);

            return redirect()->route('admin.dashboard')->with('error', 'Orders export failed: '.$e->getMessage());
        }
    }

    public function downloadExport(DataTransferJob $transfer)
    {
        abort_unless(Auth::check() && Auth::user()->isAdmin(), 403);

        if ($transfer->type !== 'export' || (($transfer->options['entity'] ?? null) !== 'orders')) {
            return redirect()->route('admin.dashboard')->with('error', 'Invalid orders export transfer selected.');
        }

        if ($transfer->status !== 'completed' || ! $transfer->result_path || ! Storage::disk('local')->exists($transfer->result_path)) {
            return redirect()->route('admin.dashboard')->with('error', 'Orders export file is not ready or no longer available.');
        }

        AuditLogger::log(
            action: 'transfer.orders_export.downloaded',
            auditable: $transfer,
            newValues: ['result_path' => $transfer->result_path],
            description: 'Completed orders export downloaded by admin.'
        );

        return response()->download(Storage::disk('local')->path($transfer->result_path), basename($transfer->result_path));
    }

    private function storeOrdersExportFile(string $format, string $resultPath, array $filters, array $columns): void
    {
        if ($format === 'pdf') {
            $export = new OrdersExport($filters, $columns);
            $orders = $export->query()->get();
            $pdf = Pdf::loadView('admin.orders.exports.pdf', [
                'orders' => $orders,
                'columns' => OrdersExport::normalizeColumns($columns),
                'headings' => OrdersExport::availableColumns(),
            ]);
            Storage::disk('local')->put($resultPath, $pdf->output());

            return;
        }

        $writerType = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;
        Excel::store(new OrdersExport($filters, $columns), $resultPath, 'local', $writerType);
    }
}
