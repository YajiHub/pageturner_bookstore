<?php

namespace App\Jobs;

use App\Exports\BooksExport;
use App\Models\DataTransferJob;
use App\Models\ExportLog;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class ProcessBooksExportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $dataTransferJobId,
        public array $filters = [],
        public array $columns = [],
        public string $format = 'xlsx',
        public ?int $exportLogId = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $transfer = DataTransferJob::find($this->dataTransferJobId);
        $exportLog = $this->exportLogId ? ExportLog::find($this->exportLogId) : null;

        if (! $transfer) {
            return;
        }

        $options = is_array($transfer->options) ? $transfer->options : [];
        $filters = $this->filters !== [] ? $this->filters : (array) ($options['filters'] ?? []);
        $columns = $this->columns !== [] ? $this->columns : (array) ($options['columns'] ?? []);
        $format = $this->format !== '' ? $this->format : (string) ($options['format'] ?? 'xlsx');

        $transfer->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
            'progress_percentage' => 5, // Start at 5% to show activity
        ]);

        $exportLog?->update([
            'status' => 'processing',
        ]);

        $filename = 'books_export_'.now()->format('Y_m_d_His').'.'.$format;
        $resultPath = 'exports/'.$filename;

        try {
            $export = new BooksExport($filters, $columns);
            $query = $export->query();
            $totalRows = $query->count();

            if ($totalRows === 0) {
                throw new \Exception("No records found matching your filters.");
            }

            if ($format === 'csv') {
                // HIGH PERFORMANCE NATIVE CSV STREAMING
                // Bypasses Laravel Excel to prevent CPU bottlenecks on massive datasets
                $disk = Storage::disk('local');
                $disk->makeDirectory('exports');
                
                $file = fopen($disk->path($resultPath), 'w');
                
                // Write Headers
                fputcsv($file, $export->headings());

                $processed = 0;
                
                // Cursor streams one row at a time instead of loading chunks into memory
                foreach ($query->cursor() as $book) {
                    fputcsv($file, $export->map($book));
                    $processed++;

                    // Update the UI progress bar every 10,000 rows
                    if ($processed % 10000 === 0) {
                        // Map the processed ratio to the 5% - 95% visual range
                        $progress = 5 + (int) (($processed / $totalRows) * 90);
                        $transfer->update(['progress_percentage' => min(95, $progress)]);
                    }
                }
                
                fclose($file);

            } elseif ($format === 'pdf') {
                // Safeguard against crashing the server with massive PDFs
                if ($totalRows > 10000) {
                    throw new \Exception("PDF export is limited to 10,000 rows to prevent memory exhaustion. Please select CSV for massive datasets.");
                }

                $books = $query->get();
                $pdf = Pdf::loadView('admin.books.exports.pdf', [
                    'books' => $books,
                    'columns' => BooksExport::normalizeColumns($columns),
                    'headings' => BooksExport::availableColumns(),
                ]);
                Storage::disk('local')->put($resultPath, $pdf->output());
                
            } else {
                // XLSX Fallback
                $writerType = ExcelWriter::XLSX;
                Excel::store($export, $resultPath, 'local', $writerType);
            }

            $transfer->update([
                'status' => 'completed',
                'result_path' => $resultPath,
                'finished_at' => now(),
                'progress_percentage' => 100,
            ]);

            $exportLog?->update([
                'status' => 'completed',
                'download_link' => route('admin.books.export.download', $transfer),
                'rows_exported' => $totalRows,
            ]);

            AuditLogger::log(
                action: 'transfer.export.completed',
                auditable: $transfer,
                newValues: ['result_path' => $resultPath, 'rows' => $totalRows],
                description: "Queued books export completed ({$totalRows} rows).",
                userId: $transfer->user_id
            );

        } catch (\Throwable $e) {
            $transfer->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
                'progress_percentage' => 100,
            ]);

            $exportLog?->update([
                'status' => 'failed',
            ]);

            AuditLogger::log(
                action: 'transfer.export.failed',
                auditable: $transfer,
                newValues: ['error_message' => $e->getMessage()],
                description: 'Queued books export failed.',
                userId: $transfer->user_id
            );
        }
    }
}