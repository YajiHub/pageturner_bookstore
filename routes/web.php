<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminUserTransferController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public routes
Route::middleware(['throttle:public-read', 'normalize.book-filters'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Book browsing (public)
    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

    // Category browsing (public)
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
});

// Cart routes
Route::get('/cart', [App\Http\Controllers\CartController::class, 'index'])->middleware('throttle:customer-actions')->name('cart.index');
Route::post('/cart/add/{book}', [App\Http\Controllers\CartController::class, 'add'])->middleware('throttle:critical-write')->name('cart.add');
Route::post('/cart/remove/{book}', [App\Http\Controllers\CartController::class, 'remove'])->middleware('throttle:critical-write')->name('cart.remove');
Route::post('/cart/update/{book}', [App\Http\Controllers\CartController::class, 'update'])->middleware('throttle:critical-write')->name('cart.update');
Route::post('/cart/clear', [App\Http\Controllers\CartController::class, 'clear'])->middleware('throttle:critical-write')->name('cart.clear');
Route::post('/cart/checkout', [App\Http\Controllers\CartController::class, 'checkout'])->middleware(['auth', 'verified', 'normalize.input', 'throttle:critical-write'])->name('cart.checkout');

// Authenticated routes (email verified required)
Route::middleware(['auth', 'verified', 'normalize.input', 'throttle:customer-actions'])->group(function () {
    // Dashboard - redirect based on role
    Route::get('/dashboard', function () {
        if (Auth::user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return app(CustomerDashboardController::class)->index();
    })->name('dashboard');
    Route::get('/dashboard/export-data', [CustomerDashboardController::class, 'exportData'])->name('dashboard.export-data');
    Route::get('/dashboard/export-orders', [CustomerDashboardController::class, 'exportOrders'])->name('dashboard.export-orders');
    Route::get('/dashboard/export-reading-history', [CustomerDashboardController::class, 'exportReadingHistory'])->name('dashboard.export-reading-history');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read/{notification}', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

    // Review routes
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->middleware('throttle:critical-write')->name('reviews.store');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->middleware('throttle:critical-write')->name('reviews.destroy');

    // Order routes
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:critical-write')->name('orders.store');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->middleware('throttle:critical-write')->name('orders.cancel');
});

// Profile routes (auth only, no email verification needed so users can verify from here)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin-only routes (Category & Book management)
Route::middleware(['auth', 'verified', 'admin', 'normalize.input', 'throttle:admin-actions'])->prefix('admin')->name('admin.')->group(function () {
    // Admin Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/transfer-jobs/progress', [AdminDashboardController::class, 'transferJobsProgress'])->name('transfer-jobs.progress');
    
    // Backups Management
    Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::get('/backups/{file_name}/download', [BackupController::class, 'download'])->name('backups.download');
    
    // Audit Logs
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');
    Route::post('/audit-logs/archive', [AuditLogController::class, 'archive'])->name('audit-logs.archive');
    Route::post('/audit-logs/verify-integrity', [AuditLogController::class, 'verifyIntegrity'])->name('audit-logs.verify-integrity');
    Route::post('/audit-logs/integrity-check', [AuditLogController::class, 'verifyIntegrity'])->name('audit-logs.integrity-check');
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->whereNumber('auditLog')->name('audit-logs.show');

    // Category management
    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('throttle:critical-write')->name('categories.store');
    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('throttle:critical-write')->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('throttle:critical-write')->name('categories.destroy');

    // Book management
    Route::match(['get', 'post'], '/books/export', [BookController::class, 'export'])->name('books.export');
    Route::get('/books/export/{transfer}/download', [BookController::class, 'downloadExport'])->name('books.export.download');
    Route::get('/books/import/template', [BookController::class, 'downloadImportTemplate'])->name('books.import.template');
    Route::post('/books/import/preview', [BookController::class, 'importPreview'])->middleware('throttle:critical-write')->name('books.import.preview');
    Route::post('/books/import/process', [BookController::class, 'importProcess'])->middleware('throttle:critical-write')->name('books.import.process');
    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
    Route::post('/books', [BookController::class, 'store'])->middleware('throttle:critical-write')->name('books.store');
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    Route::put('/books/{book}', [BookController::class, 'update'])->middleware('throttle:critical-write')->name('books.update');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->middleware('throttle:critical-write')->name('books.destroy');

    // Order management (admin)
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->middleware('throttle:critical-write')->name('orders.updateStatus');
    Route::match(['get', 'post'], '/orders/export', [OrderController::class, 'export'])->name('orders.export');
    Route::get('/orders/export/{transfer}/download', [OrderController::class, 'downloadExport'])->name('orders.export.download');

    // User import/export management
    Route::match(['get', 'post'], '/users/export', [AdminUserTransferController::class, 'export'])->name('users.export');
    Route::get('/users/export/{transfer}/download', [AdminUserTransferController::class, 'downloadExport'])->name('users.export.download');
    Route::get('/users/import/template', [AdminUserTransferController::class, 'downloadImportTemplate'])->name('users.import.template');
    Route::post('/users/import/preview', [AdminUserTransferController::class, 'importPreview'])->middleware('throttle:critical-write')->name('users.import.preview');
    Route::post('/users/import/process', [AdminUserTransferController::class, 'importProcess'])->middleware('throttle:critical-write')->name('users.import.process');
});

require __DIR__.'/auth.php';