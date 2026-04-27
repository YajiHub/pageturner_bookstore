<?php

namespace App\Imports;

use App\Models\Book;
use App\Models\Category;
use App\Models\DataTransferJob;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Validators\Failure;

class BooksImport implements ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    /**
     * @var array<int, array{row:int, attribute:string, errors:array<int, string>}>
     */
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

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->failedRows[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
            ];

            $this->failedRowMap[(int) $failure->row()] = true;
        }

        $this->syncProgress(true);
    }

    /**
     * @return array<int, array{row:int, attribute:string, errors:array<int, string>}>
     */
    public function getFailedRows(): array
    {
        return $this->failedRows;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $this->processedRows++;

        // Force isbn to string and normalize separators.
        $isbn = $row['isbn'] ?? '';
        if (is_numeric($isbn)) {
            $isbn = number_format((float) $isbn, 0, '.', '');
        }
        $isbn = $this->normalizeIsbn((string) $isbn);

        $categoryId = $this->resolveCategoryId($row);

        $payload = [
            'category_id' => $categoryId,
            'title' => $row['title'],
            'author' => $row['author'],
            'isbn' => $isbn,
            'price' => $row['price'],
            'stock_quantity' => $row['stock_quantity'] ?? $row['stock'] ?? 0,
            'description' => $row['description'] ?? null,
            'cover_image' => $row['cover_image'] ?? null,
        ];

        $existing = Book::where('isbn', $isbn)->first();
        if ($existing) {
            if ($this->duplicateStrategy === 'update') {
                $existing->fill($payload);

                $this->syncProgress();

                return $existing;
            }

            $this->syncProgress();

            return null;
        }

        $this->syncProgress();

        return new Book($payload);
    }

    /**
     * Define validation rules for rows.
     */
    public function rules(): array
    {
        return [
            '*.title' => 'required|string|max:255',
            '*.author' => 'required|string|max:255',
            '*.isbn' => ['required', function (string $attribute, mixed $value, \Closure $fail) {
                $normalized = $this->normalizeIsbn((string) $value);
                if (! preg_match('/^(?:\d{9}[\dXx]|\d{13})$/', $normalized)) {
                    $fail('ISBN must be a valid ISBN-10 or ISBN-13 format.');
                }
            }],
            '*.price' => 'required|numeric|min:0|max:9999.99',
            '*.stock_quantity' => 'nullable|integer|min:0',
            '*.stock' => 'nullable|integer|min:0',
            '*.category_id' => 'nullable|integer|exists:categories,id',
            '*.category' => 'nullable|string|exists:categories,name',
            '*.category_name' => 'nullable|string|exists:categories,name',
            '*.description' => 'nullable|string',
            '*.cover_image' => 'nullable',
        ];
    }

    /**
     * Read the file in chunks of 500 records.
     */
    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * Insert records in databases in batches of 500 records.
     */
    public function batchSize(): int
    {
        return 1000;
    }

    private function resolveCategoryId(array $row): int
    {
        if (! empty($row['category_id']) && Category::whereKey($row['category_id'])->exists()) {
            return (int) $row['category_id'];
        }

        $categoryName = $row['category'] ?? $row['category_name'] ?? null;
        if (is_string($categoryName) && trim($categoryName) !== '') {
            $category = Category::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($categoryName))])->first();
            if ($category) {
                return (int) $category->id;
            }
        }

        throw new \RuntimeException('Category must exist and be provided as category_id or category name.');
    }

    private function normalizeIsbn(string $isbn): string
    {
        return preg_replace('/[^0-9Xx]/', '', trim($isbn)) ?? $isbn;
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
