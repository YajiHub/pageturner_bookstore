<?php

namespace App\Jobs;

use App\Exports\OrdersExport;
use App\Models\DataTransferJob;
use App\Models\ExportLog;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class ProcessOrdersExportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $dataTransferJobId,
        public array $filters = [],
        public array $columns = [],
        public string $format = 'xlsx',
        public ?int $exportLogId = null
    ) {
    }

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
            'progress_percentage' => 10,
        ]);

        $exportLog?->update([
            'status' => 'processing',
        ]);

        $filename = 'orders_export_'.now()->format('Y_m_d_His').'.'.$format;
        $resultPath = 'exports/'.$filename;

        try {
            if ($format === 'pdf') {
                $export = new OrdersExport($filters, $columns);
                $orders = $export->query()->get();
                $pdf = Pdf::loadView('admin.orders.exports.pdf', [
                    'orders' => $orders,
                    'columns' => OrdersExport::normalizeColumns($columns),
                    'headings' => OrdersExport::availableColumns(),
                ]);
                Storage::disk('local')->put($resultPath, $pdf->output());
            } else {
                $writerType = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;
                Excel::store(new OrdersExport($filters, $columns), $resultPath, 'local', $writerType);
            }

            $transfer->update([
                'progress_percentage' => 90,
            ]);

            $transfer->update([
                'status' => 'completed',
                'result_path' => $resultPath,
                'finished_at' => now(),
                'progress_percentage' => 100,
            ]);

            $rowsExported = (int) (new OrdersExport($filters, $columns))->query()->count();

            $exportLog?->update([
                'status' => 'completed',
                'download_link' => route('admin.orders.export.download', $transfer),
                'rows_exported' => $rowsExported,
            ]);

            AuditLogger::log(
                action: 'transfer.orders_export.completed',
                auditable: $transfer,
                newValues: ['result_path' => $resultPath],
                description: 'Queued orders export completed.',
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
                action: 'transfer.orders_export.failed',
                auditable: $transfer,
                newValues: ['error_message' => $e->getMessage()],
                description: 'Queued orders export failed.',
                userId: $transfer->user_id
            );
        }
    }
}
