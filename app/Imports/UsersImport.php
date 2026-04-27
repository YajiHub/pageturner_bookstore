<?php

namespace App\Imports;

use App\Models\DataTransferJob;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class UsersImport implements ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    protected array $failedRows = [];
    protected int $processedRows = 0;
    protected int $lastSyncedRows = 0;
    protected array $failedRowMap = [];

    public function __construct(
        private string $duplicateStrategy = 'skip',
        private ?int $dataTransferJobId = null,
        private int $totalRows = 0
    )
    {
    }

    public function model(array $row)
    {
        $this->processedRows++;

        $email = mb_strtolower(trim((string) ($row['email'] ?? '')));
        $existing = User::where('email', $email)->first();

        $payload = [
            'name' => trim((string) ($row['name'] ?? '')),
            'email' => $email,
            'role' => in_array(($row['role'] ?? 'customer'), ['admin', 'customer'], true) ? $row['role'] : 'customer',
            'address' => $row['address'] ?? null,
        ];

        $password = (string) ($row['password'] ?? '');
        if ($password !== '') {
            $payload['password'] = Hash::make($password);
        }

        if ($existing) {
            if ($this->duplicateStrategy === 'update') {
                if (! isset($payload['password'])) {
                    unset($payload['password']);
                }

                $existing->fill($payload);

                $this->syncProgress();

                return $existing;
            }

            $this->syncProgress();

            return null;
        }

        if (! isset($payload['password'])) {
            $payload['password'] = Hash::make('TempPass@123');
        }

        $this->syncProgress();

        return new User($payload);
    }

    public function rules(): array
    {
        return [
            '*.name' => 'required|string|max:255',
            '*.email' => 'required|email|max:255',
            '*.role' => 'nullable|in:admin,customer',
            '*.address' => 'nullable|string',
            '*.password' => 'nullable|string|min:8|max:255',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->failedRows[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];

            $this->failedRowMap[(int) $failure->row()] = true;
        }

        $this->syncProgress(true);
    }

    public function getFailedRows(): array
    {
        return $this->failedRows;
    }

    private function syncProgress(bool $force = false): void
    {
        if (! $this->dataTransferJobId) {
            return;
        }

        if (! $force && ($this->processedRows - $this->lastSyncedRows) < 200) {
            return;
        }

        $failedRows = count($this->failedRowMap);
        $successfulRows = max(0, $this->processedRows - $failedRows);
        $progressNumerator = $this->processedRows + $failedRows;
        $progress = $this->totalRows > 0
            ? (int) min(95, floor(($progressNumerator / $this->totalRows) * 95))
            : 5;

        DataTransferJob::whereKey($this->dataTransferJobId)->update([
            'imported_rows' => $successfulRows,
            'failed_rows' => $failedRows,
            'progress_percentage' => max(1, $progress),
        ]);

        $this->lastSyncedRows = $this->processedRows;
    }
}
