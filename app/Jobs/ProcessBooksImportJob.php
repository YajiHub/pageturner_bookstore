<?php

namespace App\Jobs;

use App\Imports\BooksImport;
use App\Models\Book;
use App\Models\DataTransferJob;
use App\Models\ImportLog;
use App\Services\AuditLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessBooksImportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $dataTransferJobId,
        public string $duplicateStrategy = 'skip',
        public ?int $importLogId = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $transfer = DataTransferJob::find($this->dataTransferJobId);
        $importLog = $this->importLogId ? ImportLog::find($this->importLogId) : null;

        if (! $transfer) {
            return;
        }

        $transfer->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
            'progress_percentage' => 1,
        ]);

        $importLog?->update([
            'status' => 'processing',
        ]);

        if (! $transfer->stored_path || ! Storage::disk('local')->exists($transfer->stored_path)) {
            $transfer->update([
                'status' => 'failed',
                'error_message' => 'Import file is missing or expired.',
                'finished_at' => now(),
            ]);

            $importLog?->update([
                'status' => 'failed',
                'failure_report' => ['Import file is missing or expired.'],
            ]);

            return;
        }

        $beforeCount = Book::count();
        $duplicateStrategy = $this->duplicateStrategy;
        if ($duplicateStrategy === '' || $duplicateStrategy === null) {
            $duplicateStrategy = (string) ($transfer->options['duplicate_strategy'] ?? 'skip');
        }
        $readerType = $transfer->options['reader_type'] ?? null;
        if (! is_string($readerType) || $readerType === '') {
            $readerType = null;
        }
        $import = new BooksImport($duplicateStrategy, $transfer->id, (int) ($transfer->total_rows ?? 0));

        try {
            Excel::import($import, $transfer->stored_path, 'local', $readerType);

            $failures = $import->getFailedRows();

            $afterCount = Book::count();

            $transfer->update([
                'status' => 'completed',
                'imported_rows' => max(0, $afterCount - $beforeCount),
                'failed_rows' => count($failures),
                'failures' => $failures ?: null,
                'finished_at' => now(),
                'progress_percentage' => 100,
            ]);

            $importLog?->update([
                'status' => 'completed',
                'rows_processed' => max(0, $afterCount - $beforeCount),
                'rows_failed' => count($failures),
                'failure_report' => $failures ?: null,
            ]);

            AuditLogger::log(
                action: 'transfer.import.completed',
                auditable: $transfer,
                newValues: [
                    'imported_rows' => $transfer->imported_rows,
                    'failed_rows' => $transfer->failed_rows,
                ],
                description: 'Queued books import completed.',
                userId: $transfer->user_id
            );
        } catch (\Throwable $e) {
            $transfer->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
                'progress_percentage' => 100,
            ]);

            $importLog?->update([
                'status' => 'failed',
                'failure_report' => [$e->getMessage()],
            ]);

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
}
