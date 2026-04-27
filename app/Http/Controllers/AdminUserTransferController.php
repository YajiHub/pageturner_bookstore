<?php

namespace App\Http\Controllers;

use App\Exports\UsersExport;
use App\Jobs\ProcessUsersExportJob;
use App\Jobs\ProcessUsersImportJob;
use App\Models\DataTransferJob;
use App\Models\ExportLog;
use App\Models\ImportLog;
use App\Support\ExcelReaderTypeResolver;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class AdminUserTransferController extends Controller
{
    private const REQUIRED_IMPORT_HEADERS = ['name', 'email', 'role', 'address', 'password'];

    public function importPreview(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $path = $file->store('imports', 'local');
        $readerType = ExcelReaderTypeResolver::fromFilename($originalName);

        $data = Excel::toArray(new \stdClass, $path, 'local', $readerType);
        $rows = $data[0] ?? [];

        $totalRows = count($rows);
        $headers = $totalRows > 0 ? $rows[0] : [];
        $normalizedHeaders = array_map(fn ($header) => $this->normalizeImportHeader((string) $header), $headers);

        if ($normalizedHeaders !== self::REQUIRED_IMPORT_HEADERS) {
            Storage::disk('local')->delete($path);

            return redirect()->route('admin.dashboard')->with('error', 'Invalid user import template headers. Required headers: '.implode(', ', self::REQUIRED_IMPORT_HEADERS).'.');
        }

        $dataRows = $totalRows > 1 ? array_slice($rows, 1, 5) : [];
        $recordCount = max(0, $totalRows - 1);

        return view('admin.users.import_preview', compact('path', 'originalName', 'headers', 'dataRows', 'recordCount'));
    }

    public function importProcess(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);

        $request->validate([
            'filepath' => 'required|string',
            'duplicate_strategy' => 'required|in:skip,update',
        ]);

        $path = $request->input('filepath');
        $originalName = $request->input('original_name');
        $duplicateStrategy = $request->input('duplicate_strategy', 'skip');
        $readerType = ExcelReaderTypeResolver::fromFilename($originalName ?: $path);

        if (! Storage::disk('local')->exists($path)) {
            return redirect()->route('admin.dashboard')->with('error', 'User import file expired or not found.');
        }

        $transfer = DataTransferJob::create([
            'user_id' => auth()->id(),
            'type' => 'import',
            'status' => 'queued',
            'progress_percentage' => 0,
            'original_filename' => $originalName,
            'stored_path' => $path,
            'options' => [
                'entity' => 'users',
                'duplicate_strategy' => $duplicateStrategy,
                'reader_type' => $readerType,
            ],
            'total_rows' => max(0, (int) $request->input('record_count', 0)),
        ]);

        $importLog = ImportLog::create([
            'user_id' => auth()->id(),
            'data_transfer_job_id' => $transfer->id,
            'filename' => $originalName,
            'status' => 'queued',
        ]);

        AuditLogger::log(
            action: 'transfer.users_import.queued',
            auditable: $transfer,
            newValues: [
                'original_filename' => $originalName,
                'stored_path' => $path,
                'duplicate_strategy' => $duplicateStrategy,
                'reader_type' => $readerType,
            ],
            description: 'Users import queued by admin.'
        );

        ProcessUsersImportJob::dispatch($transfer->id, $duplicateStrategy, $importLog->id);

        return redirect()->route('admin.dashboard')->with('success', 'User import queued. Track progress in Data Transfer Jobs.');
    }

    public function downloadImportTemplate()
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);

        $headers = implode(',', self::REQUIRED_IMPORT_HEADERS);
        $sample = 'John Example,john@example.com,customer,12 Example Road,TempPass@123';
        $content = $headers.PHP_EOL.$sample.PHP_EOL;

        return response($content)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="users_import_template.csv"');
    }

    public function export(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'format' => 'nullable|in:xlsx,csv,pdf',
            'columns' => 'nullable|array',
            'columns.*' => 'string',
            'role' => 'nullable|in:admin,customer',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'search' => 'nullable|string|max:255',
            'redact_email' => 'nullable|boolean',
            'redact_address' => 'nullable|boolean',
        ]);

        $format = $validated['format'] ?? 'xlsx';
        $columns = UsersExport::normalizeColumns($validated['columns'] ?? []);
        $filters = [
            'role' => $validated['role'] ?? null,
            'from_date' => $validated['from_date'] ?? null,
            'to_date' => $validated['to_date'] ?? null,
            'search' => $validated['search'] ?? null,
        ];
        $privacyOptions = [
            'redact_email' => (bool) ($validated['redact_email'] ?? false),
            'redact_address' => (bool) ($validated['redact_address'] ?? false),
        ];

        $exportQuery = (new UsersExport($filters, $columns, $privacyOptions))->query();
        $totalRows = (clone $exportQuery)->count();
        $filename = 'users_export_'.now()->format('Y_m_d_His').'.'.$format;

        $transfer = DataTransferJob::create([
            'user_id' => auth()->id(),
            'type' => 'export',
            'status' => $totalRows > 10000 ? 'queued' : 'processing',
            'progress_percentage' => $totalRows > 10000 ? 0 : 90,
            'original_filename' => $filename,
            'options' => [
                'entity' => 'users',
                'format' => $format,
                'filters' => $filters,
                'columns' => $columns,
                'privacy' => $privacyOptions,
            ],
            'total_rows' => $totalRows,
        ]);

        $exportLog = ExportLog::create([
            'user_id' => auth()->id(),
            'data_transfer_job_id' => $transfer->id,
            'format' => $format,
            'filters' => array_merge($filters, ['privacy' => $privacyOptions]),
            'selected_columns' => $columns,
            'status' => $totalRows > 10000 ? 'queued' : 'processing',
        ]);

        AuditLogger::log(
            action: 'transfer.users_export.queued',
            auditable: $transfer,
            newValues: [
                'original_filename' => $transfer->original_filename,
                'format' => $format,
                'total_rows' => $totalRows,
                'filters' => $filters,
                'privacy' => $privacyOptions,
                'selected_columns' => $columns,
            ],
            description: 'Users export queued by admin.'
        );

        if ($totalRows > 10000) {
            ProcessUsersExportJob::dispatch($transfer->id, $filters, $columns, $format, $privacyOptions, $exportLog->id);

            return redirect()->route('admin.dashboard')->with('success', 'Users export queued. Refresh Data Transfer Jobs to download when ready.');
        }

        $resultPath = 'exports/'.$filename;

        try {
            $this->storeUsersExportFile($format, $resultPath, $filters, $columns, $privacyOptions);

            $transfer->update([
                'status' => 'completed',
                'result_path' => $resultPath,
                'finished_at' => now(),
                'progress_percentage' => 100,
            ]);

            $exportLog->update([
                'status' => 'completed',
                'download_link' => route('admin.users.export.download', $transfer),
                'rows_exported' => $totalRows,
            ]);

            AuditLogger::log(
                action: 'transfer.users_export.completed',
                auditable: $transfer,
                newValues: ['result_path' => $resultPath],
                description: 'Users export completed synchronously by admin.'
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

            return redirect()->route('admin.dashboard')->with('error', 'Users export failed: '.$e->getMessage());
        }
    }

    public function downloadExport(DataTransferJob $transfer)
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);

        if ($transfer->type !== 'export' || (($transfer->options['entity'] ?? null) !== 'users')) {
            return redirect()->route('admin.dashboard')->with('error', 'Invalid users export transfer selected.');
        }

        if ($transfer->status !== 'completed' || ! $transfer->result_path || ! Storage::disk('local')->exists($transfer->result_path)) {
            return redirect()->route('admin.dashboard')->with('error', 'Users export file is not ready or no longer available.');
        }

        AuditLogger::log(
            action: 'transfer.users_export.downloaded',
            auditable: $transfer,
            newValues: ['result_path' => $transfer->result_path],
            description: 'Completed users export downloaded by admin.'
        );

        return response()->download(Storage::disk('local')->path($transfer->result_path), basename($transfer->result_path));
    }

    private function normalizeImportHeader(string $header): string
    {
        return str_replace(' ', '_', mb_strtolower(trim($header)));
    }

    private function storeUsersExportFile(string $format, string $resultPath, array $filters, array $columns, array $privacyOptions): void
    {
        if ($format === 'pdf') {
            $export = new UsersExport($filters, $columns, $privacyOptions);
            $users = $export->query()->get();
            $pdf = Pdf::loadView('admin.users.exports.pdf', [
                'users' => $users,
                'columns' => UsersExport::normalizeColumns($columns),
                'headings' => UsersExport::availableColumns(),
                'privacyOptions' => $privacyOptions,
            ]);
            Storage::disk('local')->put($resultPath, $pdf->output());

            return;
        }

        $writerType = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;
        Excel::store(new UsersExport($filters, $columns, $privacyOptions), $resultPath, 'local', $writerType);
    }
}
