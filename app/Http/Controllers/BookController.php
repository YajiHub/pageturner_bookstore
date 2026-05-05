<?php

namespace App\Http\Controllers;

use App\Exports\BooksExport;
use App\Jobs\ProcessBooksExportJob;
use App\Jobs\ProcessBooksImportJob;
use App\Models\Book;
use App\Models\Category;
use App\Models\DataTransferJob;
use App\Models\ExportLog;
use App\Models\ImportLog;
use App\Models\ReadingHistory;
use App\Models\User;
use App\Notifications\BookCatalogUpdatedNotification;
use App\Support\ExcelReaderTypeResolver;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\WithLimit;

class BookController extends Controller
{
    private const REQUIRED_IMPORT_HEADERS = ['title', 'author', 'isbn', 'price', 'stock', 'category', 'description'];

    private const IMPORT_HEADER_ALIASES = [
        'stock_quantity' => 'stock',
        'category_name' => 'category',
    ];

    public function index(Request $request)
    {
        $query = Book::with('category');

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('search')) {
            $search = mb_strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(author) LIKE ?', ["%{$search}%"]);
            });
        }

        if ($request->filled('min_price') && is_numeric($request->min_price)) {
            $min = floatval($request->min_price);
            $query->where('price', '>=', $min);
        }

        if ($request->filled('max_price') && is_numeric($request->max_price)) {
            $max = floatval($request->max_price);
            $query->where('price', '<=', $max);
        }

        $sort = $request->get('sort', 'date');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'rating':
                $reviewsSub = DB::table('reviews')
                    ->selectRaw('book_id, AVG(rating) as avg_rating')
                    ->groupBy('book_id');

                $query->leftJoinSub($reviewsSub, 'r', function ($join) {
                    $join->on('books.id', '=', 'r.book_id');
                })
                    ->select('books.*')
                    ->orderByRaw('COALESCE(r.avg_rating, 0) desc')
                    ->orderBy('books.id', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc')->orderBy('id', 'asc');
        }

        $books = $query->paginate(12);
        $categories = Category::all();

        return view('books.index', compact('books', 'categories'));
    }

    public function create()
    {
        $this->authorize('create', Book::class);
        $categories = Category::all();
        return view('books.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Book::class);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'required|string|unique:books',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('cover_image')) {
            $image = $request->file('cover_image');
            if (method_exists($image, 'isValid') && ! $image->isValid()) {
                return back()->withErrors(['cover_image' => 'Upload failed (PHP reported an error).']);
            }

            $imagePath = is_string($image) ? $image : $image->getRealPath();
            $destDir = storage_path('app/public/covers');
            if (! file_exists($destDir)) { @mkdir($destDir, 0755, true); }

            $filename = uniqid().'.'.$image->getClientOriginalExtension();
            $destPath = $destDir.'/'.$filename;

            $err = null;
            $ok = $this->resizeAndSaveWithGd($imagePath, $destPath, 400, 600, 85, $err);
            if (! $ok) {
                return back()->withErrors(['cover_image' => $err ?? 'Failed to process image.']);
            }

            $validated['cover_image'] = 'covers/'.$filename;
        }

        $book = Book::create($validated);

        AuditLogger::log(
            action: 'book.created',
            auditable: $book,
            newValues: $book->only(['category_id', 'title', 'author', 'isbn', 'price', 'stock_quantity']),
            description: 'Book created by admin.'
        );

        $this->notifyAdminBookCatalogUpdate('created', $book->title, $book->id);

        return redirect()->route('books.index')->with('success', 'Book added successfully!');
    }

    public function show(Book $book)
    {
        $book->load(['category', 'reviews.user']);

        $hasPurchased = false;
        $userReview = null;
        if (Auth::check()) {
            $currentUser = Auth::user();

            $hasPurchased = $currentUser->orders()
                ->where('status', 'completed')
                ->whereHas('orderItems', function ($query) use ($book) {
                    $query->where('book_id', $book->id);
                })->exists();

            if (! $currentUser->isAdmin()) {
                $userReview = $book->reviews->firstWhere('user_id', $currentUser->id);
            }

            if (! $currentUser->isAdmin() && Schema::hasTable('reading_histories')) {
                $history = ReadingHistory::firstOrNew([
                    'user_id' => Auth::id(),
                    'book_id' => $book->id,
                    'order_id' => null,
                    'event_type' => 'viewed',
                ]);
                $history->quantity = ($history->exists ? (int) $history->quantity : 0) + 1;
                $history->last_seen_at = now();
                $history->save();
            }
        }

        return view('books.show', compact('book', 'hasPurchased', 'userReview'));
    }

    public function edit(Book $book)
    {
        $this->authorize('update', $book);
        $categories = Category::all();
        return view('books.edit', compact('book', 'categories'));
    }

    public function update(Request $request, Book $book)
    {
        $this->authorize('update', $book);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'required|string|unique:books,isbn,'.$book->id,
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('cover_image')) {
            $image = $request->file('cover_image');
            $imagePath = is_string($image) ? $image : $image->getRealPath();
            $destDir = storage_path('app/public/covers');
            if (! file_exists($destDir)) { @mkdir($destDir, 0755, true); }

            $filename = uniqid().'.'.$image->getClientOriginalExtension();
            $destPath = $destDir.'/'.$filename;

            $err = null;
            $ok = $this->resizeAndSaveWithGd($imagePath, $destPath, 400, 600, 85, $err);
            if (! $ok) { return back()->withErrors(['cover_image' => $err ?? 'Failed to process image.']); }

            $validated['cover_image'] = 'covers/'.$filename;
        }

        $before = $book->only(['category_id', 'title', 'author', 'isbn', 'price', 'stock_quantity', 'description', 'cover_image']);
        $book->update($validated);

        AuditLogger::log(
            action: 'book.updated',
            auditable: $book,
            oldValues: $before,
            newValues: $book->only(['category_id', 'title', 'author', 'isbn', 'price', 'stock_quantity', 'description', 'cover_image']),
            description: 'Book updated by admin.'
        );

        $this->notifyAdminBookCatalogUpdate('updated', $book->title, $book->id);

        return redirect()->route('books.show', $book)->with('success', 'Book updated successfully!');
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);
        $snapshot = $book->only(['category_id', 'title', 'author', 'isbn', 'price', 'stock_quantity']);
        $book->delete();

        AuditLogger::log(
            action: 'book.deleted',
            oldValues: $snapshot,
            description: 'Book deleted by admin.'
        );

        $this->notifyAdminBookCatalogUpdate('deleted', (string) ($snapshot['title'] ?? 'Unknown'), null);

        return redirect()->route('books.index')->with('success', 'Book deleted successfully!');
    }

    private function notifyAdminBookCatalogUpdate(string $action, string $bookTitle, ?int $bookId): void
    {
        $actorId = Auth::id();
        $actorName = Auth::user()?->name ?? 'System';

        User::where('role', 'admin')
            ->when($actorId, fn ($query) => $query->where('id', '!=', $actorId))
            ->each(function (User $admin) use ($action, $bookTitle, $bookId, $actorName): void {
                $admin->notify(new BookCatalogUpdatedNotification($action, $bookTitle, $bookId, $actorName));
            });
    }

    public function importPreview(Request $request)
    {
        $this->authorize('create', Book::class);

        if (!$request->hasFile('file')) {
            return redirect()->route('admin.dashboard')->with('error', 'Upload failed. The file is too large and exceeds your PHP post_max_size limit. Please update php.ini.');
        }

        $file = $request->file('file');

        if (!$file->isValid()) {
            return redirect()->route('admin.dashboard')->with('error', 'Upload failed: ' . $file->getErrorMessage() . '. Please increase upload_max_filesize in your php.ini.');
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:512000', 
        ]);

        $originalName = $file->getClientOriginalName();
        $path = $file->store('imports', 'local');
        $fullPath = Storage::disk('local')->path($path);
        $extension = strtolower($file->getClientOriginalExtension());

        $headers = [];
        $dataRows = [];
        $recordCount = 0;

        try {
            if ($extension === 'csv') {
                if (($handle = fopen($fullPath, 'r')) !== false) {
                    $headerRow = fgetcsv($handle);
                    if (is_array($headerRow) && !empty($headerRow)) {
                        $headerRow[0] = preg_replace('/^[\xef\xbb\xbf]+/', '', $headerRow[0]); // Strip UTF-8 BOM
                        $headers = $headerRow;
                    }
                    
                    while (($data = fgetcsv($handle)) !== false && count($dataRows) < 5) {
                        if (!empty(array_filter($data))) {
                            $dataRows[] = $data;
                        }
                    }
                    
                    fseek($handle, 0);
                    $lines = 0;
                    while (fgets($handle) !== false) { 
                        $lines++; 
                    }
                    $recordCount = max(0, $lines - 1);
                    
                    fclose($handle);
                }
            } else {
                $readerType = ExcelReaderTypeResolver::fromFilename($originalName);
                $data = Excel::toArray(new class implements WithLimit {
                    public function limit(): int { return 6; }
                }, $path, 'local', $readerType);

                $rows = $data[0] ?? [];
                $headers = count($rows) > 0 ? $rows[0] : [];
                $dataRows = count($rows) > 1 ? array_slice($rows, 1, 5) : [];
                $recordCount = count($rows) > 1 ? count($rows) - 1 : 0; 
            }
        } catch (\Exception $e) {
            Storage::disk('local')->delete($path);
            return redirect()->route('admin.dashboard')->with('error', 'Error reading file preview: ' . $e->getMessage());
        }

        if (empty($headers)) {
            Storage::disk('local')->delete($path);
            return redirect()->route('admin.dashboard')->with('error', 'The uploaded file appears to be empty or corrupted.');
        }

        $normalizedHeaders = array_map(fn ($header) => $this->normalizeImportHeader((string) $header), $headers);

        if (! $this->isSupportedImportHeaderSet($normalizedHeaders)) {
            Storage::disk('local')->delete($path);
            return redirect()->route('admin.dashboard')->with('error', 'Invalid import headers. Must contain title, author, isbn, price, stock, and category.');
        }

        return view('admin.books.import_preview', compact('path', 'originalName', 'headers', 'dataRows', 'recordCount'));
    }

    public function importProcess(Request $request)
    {
        $this->authorize('create', Book::class);

        $request->validate([
            'filepath' => 'required|string',
            'duplicate_strategy' => 'required|in:skip,update',
        ]);

        $path = $request->input('filepath');
        $originalName = $request->input('original_name');
        $duplicateStrategy = $request->input('duplicate_strategy', 'skip');
        $readerType = ExcelReaderTypeResolver::fromFilename($originalName ?: $path);

        if (! Storage::disk('local')->exists($path)) {
            return redirect()->route('admin.dashboard')->with('error', 'Import file expired or not found.');
        }

        $transfer = DataTransferJob::create([
            'user_id' => Auth::id(),
            'type' => 'import',
            'status' => 'queued',
            'progress_percentage' => 0,
            'original_filename' => $originalName,
            'stored_path' => $path,
            'options' => [
                'duplicate_strategy' => $duplicateStrategy,
                'reader_type' => $readerType,
            ],
            'total_rows' => max(0, (int) $request->input('record_count', 0)),
        ]);

        $importLog = ImportLog::create([
            'user_id' => Auth::id(),
            'data_transfer_job_id' => $transfer->id,
            'filename' => $originalName,
            'status' => 'queued',
        ]);

        AuditLogger::log(
            action: 'transfer.import.queued',
            auditable: $transfer,
            newValues: ['original_filename' => $originalName, 'stored_path' => $path, 'duplicate_strategy' => $duplicateStrategy],
            description: 'Books import queued by admin.'
        );

        ProcessBooksImportJob::dispatch($transfer->id, $duplicateStrategy, $importLog->id);

        return redirect()->route('admin.dashboard')->with('success', 'Import queued. Track progress in Data Transfer Jobs.');
    }

    public function downloadImportTemplate()
    {
        $this->authorize('create', Book::class);

        $headers = implode(',', self::REQUIRED_IMPORT_HEADERS);
        $sample = 'Sample Book,Sample Author,9781234567897,19.99,15,Fiction,Sample description';
        $content = $headers.PHP_EOL.$sample.PHP_EOL;

        return response($content)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="books_import_template.csv"');
    }

    public function export(Request $request)
    {
        $this->authorize('create', Book::class);

        $validated = $request->validate([
            'format' => 'nullable|in:xlsx,csv,pdf',
            'columns' => 'nullable|array',
            'columns.*' => 'string',
            'category_id' => 'nullable|integer|exists:categories,id',
            'search' => 'nullable|string|max:255',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'stock_status' => 'nullable|in:in_stock,out_of_stock',
        ]);

        $format = $validated['format'] ?? 'xlsx';
        $columns = BooksExport::normalizeColumns($validated['columns'] ?? []);
        $filters = [
            'category_id' => $validated['category_id'] ?? null,
            'search' => $validated['search'] ?? null,
            'min_price' => $validated['min_price'] ?? null,
            'max_price' => $validated['max_price'] ?? null,
            'stock_status' => $validated['stock_status'] ?? null,
        ];

        $exportQuery = (new BooksExport($filters, $columns))->query();
        $totalRows = (clone $exportQuery)->count();
        $filename = 'books_export_'.now()->format('Y_m_d_His').'.'.$format;

        $transfer = DataTransferJob::create([
            'user_id' => Auth::id(),
            'type' => 'export',
            'status' => $totalRows > 10000 ? 'queued' : 'processing',
            'progress_percentage' => $totalRows > 10000 ? 0 : 90,
            'original_filename' => $filename,
            'options' => [
                'entity' => 'books',
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
            action: 'transfer.export.queued',
            auditable: $transfer,
            newValues: ['original_filename' => $transfer->original_filename, 'format' => $format, 'total_rows' => $totalRows],
            description: 'Books export queued by admin.'
        );

        if ($totalRows > 10000) {
            // FIXED: Using the native ProcessBooksExportJob we built instead of QueuedBooksExportJob
            ProcessBooksExportJob::dispatch($transfer->id, $filters, $columns, $format, $exportLog->id);
            return redirect()->route('admin.dashboard')->with('success', 'Export queued. Refresh Data Transfer Jobs to download when ready.');
        }

        $resultPath = 'exports/'.$filename;

        try {
            $this->storeExportFile($format, $resultPath, $filters, $columns);

            $transfer->update([
                'status' => 'completed',
                'result_path' => $resultPath,
                'finished_at' => now(),
                'progress_percentage' => 100,
            ]);

            $exportLog->update([
                'status' => 'completed',
                'download_link' => route('admin.books.export.download', $transfer),
                'rows_exported' => $totalRows,
            ]);

            AuditLogger::log(
                action: 'transfer.export.completed',
                auditable: $transfer,
                newValues: ['result_path' => $resultPath],
                description: 'Books export completed synchronously by admin.'
            );

            return response()->download(Storage::disk('local')->path($resultPath), basename($resultPath));
        } catch (\Throwable $e) {
            $transfer->update(['status' => 'failed', 'error_message' => $e->getMessage(), 'progress_percentage' => 100]);
            $exportLog->update(['status' => 'failed']);
            return redirect()->route('admin.dashboard')->with('error', 'Export failed: '.$e->getMessage());
        }
    }

    public function downloadExport(DataTransferJob $transfer)
    {
        $this->authorize('create', Book::class);

        if ($transfer->type !== 'export' || (($transfer->options['entity'] ?? 'books') !== 'books')) {
            return redirect()->route('admin.dashboard')->with('error', 'Invalid export transfer selected.');
        }

        if ($transfer->status !== 'completed' || ! $transfer->result_path || ! Storage::disk('local')->exists($transfer->result_path)) {
            return redirect()->route('admin.dashboard')->with('error', 'Export file is not ready or no longer available.');
        }

        AuditLogger::log(action: 'transfer.export.downloaded', auditable: $transfer, newValues: ['result_path' => $transfer->result_path], description: 'Completed export downloaded by admin.');

        return response()->download(Storage::disk('local')->path($transfer->result_path), basename($transfer->result_path));
    }

    private function normalizeImportHeader(string $header): string
    {
        return str_replace(' ', '_', mb_strtolower(trim($header)));
    }

    private function normalizeImportHeaderAlias(string $header): string
    {
        return self::IMPORT_HEADER_ALIASES[$header] ?? $header;
    }

    private function isSupportedImportHeaderSet(array $headers): bool
    {
        $normalized = array_map(fn (string $header) => $this->normalizeImportHeaderAlias($header), $headers);
        $headerSet = array_fill_keys($normalized, true);

        foreach (['title', 'author', 'isbn', 'price', 'stock', 'category'] as $column) {
            if (! isset($headerSet[$column])) { return false; }
        }
        return true;
    }

    private function storeExportFile(string $format, string $resultPath, array $filters, array $columns): void
    {
        if ($format === 'pdf') {
            $export = new BooksExport($filters, $columns);
            $books = $export->query()->get();
            $pdf = Pdf::loadView('admin.books.exports.pdf', [
                'books' => $books,
                'columns' => BooksExport::normalizeColumns($columns),
                'headings' => BooksExport::availableColumns(),
            ]);
            Storage::disk('local')->put($resultPath, $pdf->output());
            return;
        }

        $writerType = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;
        Excel::store(new BooksExport($filters, $columns), $resultPath, 'local', $writerType);
    }

    protected function resizeAndSaveWithGd(string $srcPath, string $destPath, int $maxW, int $maxH, int $quality = 85, ?string &$error = null): bool
    {
        $error = null;
        if (! extension_loaded('gd')) { $error = 'GD extension is not available.'; return false; }
        $info = @getimagesize($srcPath);
        if ($info === false) { $error = 'Unable to read image information.'; return false; }

        [$w, $h, $type] = [$info[0], $info[1], $info[2]];
        if ($w <= 0 || $h <= 0 || ($w * $h) > 12000000) { $error = 'Invalid image or too large.'; return false; }

        $ratio = min($maxW / $w, $maxH / $h, 1);
        $nw = max(1, (int) round($w * $ratio));
        $nh = max(1, (int) round($h * $ratio));
        $dst = imagecreatetruecolor($nw, $nh);

        switch ($type) {
            case IMAGETYPE_JPEG: $src = @imagecreatefromjpeg($srcPath); break;
            case IMAGETYPE_PNG: 
                $src = @imagecreatefrompng($srcPath); 
                imagealphablending($dst, false); imagesavealpha($dst, true);
                imagefilledrectangle($dst, 0, 0, $nw, $nh, imagecolorallocatealpha($dst, 0, 0, 0, 127));
                break;
            default: return false;
        }

        if (! imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h)) { return false; }

        $ok = $type === IMAGETYPE_JPEG ? imagejpeg($dst, $destPath, $quality) : imagepng($dst, $destPath, 6);

        imagedestroy($src); imagedestroy($dst);
        return (bool) $ok;
    }
}