<?php

namespace App\Jobs;

use App\Imports\BooksImport;
use App\Models\Book;
use App\Models\Category;
use App\Models\DataTransferJob;
use App\Models\ImportLog;
use App\Services\AuditLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessBooksImportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $dataTransferJobId,
        public string $duplicateStrategy = 'skip',
        public ?int $importLogId = null
    ) {
    }

    public function handle(): void
    {
        $transfer = DataTransferJob::find($this->dataTransferJobId);
        $importLog = $this->importLogId ? ImportLog::find($this->importLogId) : null;

        if (! $transfer) return;

        $transfer->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
            'progress_percentage' => 1,
        ]);

        $importLog?->update(['status' => 'processing']);

        if (! $transfer->stored_path || ! Storage::disk('local')->exists($transfer->stored_path)) {
            $transfer->update(['status' => 'failed', 'error_message' => 'Import file is missing or expired.', 'finished_at' => now()]);
            $importLog?->update(['status' => 'failed', 'failure_report' => ['Import file is missing or expired.']]);
            return;
        }

        $fullPath = Storage::disk('local')->path($transfer->stored_path);
        $extension = strtolower(pathinfo($transfer->original_filename, PATHINFO_EXTENSION));
        
        $duplicateStrategy = $this->duplicateStrategy;
        if ($duplicateStrategy === '' || $duplicateStrategy === null) {
            $duplicateStrategy = (string) ($transfer->options['duplicate_strategy'] ?? 'skip');
        }

        try {
            if ($extension === 'csv') {
                $this->processNativeCsv($fullPath, $transfer, $importLog, $duplicateStrategy);
            } 
            else {
                $readerType = $transfer->options['reader_type'] ?? null;
                $import = new BooksImport($duplicateStrategy, $transfer->id, (int) ($transfer->total_rows ?? 0));
                Excel::import($import, $transfer->stored_path, 'local', $readerType);
                
                $failures = $import->getFailedRows();
                
                $transfer->update([
                    'status' => 'completed',
                    'failures' => $failures ?: null,
                    'finished_at' => now(),
                    'progress_percentage' => 100,
                ]);

                $importLog?->update([
                    'status' => 'completed',
                    'failure_report' => $failures ?: null,
                ]);
            }

            AuditLogger::log(
                action: 'transfer.import.completed',
                auditable: $transfer,
                newValues: ['imported_rows' => $transfer->imported_rows, 'failed_rows' => $transfer->failed_rows],
                description: 'Queued books import completed.',
                userId: $transfer->user_id
            );

        } catch (\Throwable $e) {
            $transfer->update(['status' => 'failed', 'error_message' => $e->getMessage(), 'finished_at' => now(), 'progress_percentage' => 100]);
            $importLog?->update(['status' => 'failed', 'failure_report' => [$e->getMessage()]]);

            AuditLogger::log(
                action: 'transfer.import.failed',
                auditable: $transfer,
                newValues: ['error_message' => $e->getMessage()],
                description: 'Queued books import failed.',
                userId: $transfer->user_id
            );
        } finally {
            Storage::disk('local')->delete($transfer->stored_path);
        }
    }

    private function processNativeCsv(string $fullPath, $transfer, $importLog, string $duplicateStrategy): void
    {
        $handle = fopen($fullPath, 'r');
        if ($handle === false) throw new \Exception("Failed to open CSV file for streaming.");

        $headers = fgetcsv($handle);
        if (isset($headers[0])) { $headers[0] = preg_replace('/^[\xef\xbb\xbf]+/', '', $headers[0]); } // Strip BOM
        
        // FIXED: Convert header spaces to underscores natively (e.g. "Category ID" -> "category_id")
        $headerMap = array_flip(array_map(fn($h) => str_replace(' ', '_', strtolower(trim($h))), $headers));

        $colIdx = [
            'title' => $headerMap['title'] ?? null,
            'author' => $headerMap['author'] ?? null,
            'isbn' => $headerMap['isbn'] ?? null,
            'price' => $headerMap['price'] ?? null,
            'stock' => $headerMap['stock'] ?? $headerMap['stock_quantity'] ?? null,
            'description' => $headerMap['description'] ?? null,
            'category_id' => $headerMap['category_id'] ?? null,
            'category_name' => $headerMap['category'] ?? $headerMap['category_name'] ?? null,
        ];

        $categoryMap = Category::pluck('id', 'name')->mapWithKeys(fn($id, $name) => [mb_strtolower(trim($name)) => $id])->toArray();
        $categoryIdMap = Category::pluck('id', 'id')->toArray();

        $batch = [];
        $batchSize = 5000;
        $processed = 0;
        $failed = 0;
        $totalRows = max(1, $transfer->total_rows);
        $now = now()->format('Y-m-d H:i:s');

        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) continue; 

            $title = $row[$colIdx['title']] ?? null;
            $isbn = $row[$colIdx['isbn']] ?? null;
            
            if (! $title || ! $isbn) { $failed++; continue; }

            $isbn = preg_replace('/[^0-9Xx]/', '', trim((string) $isbn));
            
            $catId = null;
            if ($colIdx['category_id'] !== null && isset($row[$colIdx['category_id']], $categoryIdMap[(int)$row[$colIdx['category_id']]])) {
                $catId = (int) $row[$colIdx['category_id']];
            } elseif ($colIdx['category_name'] !== null && !empty($row[$colIdx['category_name']])) {
                $cName = mb_strtolower(trim($row[$colIdx['category_name']]));
                $catId = $categoryMap[$cName] ?? null;
            }

            if (! $catId) { $failed++; continue; }

            $batch[] = [
                'category_id' => $catId,
                'title' => trim($title),
                'author' => trim($row[$colIdx['author']] ?? 'Unknown'),
                'isbn' => $isbn,
                'price' => (float) ($row[$colIdx['price']] ?? 0),
                'stock_quantity' => (int) ($row[$colIdx['stock']] ?? 0),
                'description' => $row[$colIdx['description']] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $processed++;

            if (count($batch) >= $batchSize) {
                if ($duplicateStrategy === 'update') {
                    DB::table('books')->upsert($batch, ['isbn'], ['category_id', 'title', 'author', 'price', 'stock_quantity', 'description', 'updated_at']);
                } else {
                    DB::table('books')->insertOrIgnore($batch);
                }
                $batch = [];
                $progress = 1 + (int) (($processed / $totalRows) * 98);
                $transfer->update(['progress_percentage' => min(99, $progress), 'imported_rows' => $processed, 'failed_rows' => $failed]);
            }
        }

        if (count($batch) > 0) {
            if ($duplicateStrategy === 'update') {
                DB::table('books')->upsert($batch, ['isbn'], ['category_id', 'title', 'author', 'price', 'stock_quantity', 'description', 'updated_at']);
            } else {
                DB::table('books')->insertOrIgnore($batch);
            }
        }

        $transfer->update(['imported_rows' => $processed, 'failed_rows' => $failed, 'status' => 'completed', 'progress_percentage' => 100, 'finished_at' => now()]);
        $importLog?->update(['status' => 'completed', 'rows_processed' => $processed, 'rows_failed' => $failed]);

        fclose($handle);
    }
}