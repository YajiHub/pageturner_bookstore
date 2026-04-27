<?php

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BooksExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    use Exportable;

    private const AVAILABLE_COLUMNS = [
        'id' => 'ID',
        'category_id' => 'Category ID',
        'category_name' => 'Category Name',
        'title' => 'Title',
        'author' => 'Author',
        'isbn' => 'ISBN',
        'price' => 'Price',
        'stock_quantity' => 'Stock Quantity',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ];

    public function __construct(
        private readonly array $filters = [],
        private readonly array $selectedColumns = []
    ) {
    }

    /**
     * Stream data using raw DB query instead of loading heavy Eloquent models.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query(): Builder
    {
        $query = DB::table('books')
            ->leftJoin('categories', 'books.category_id', '=', 'categories.id')
            ->select('books.*', 'categories.name as joined_category_name');

        if (! empty($this->filters['category_id'])) {
            $query->where('books.category_id', (int) $this->filters['category_id']);
        }

        if (! empty($this->filters['search'])) {
            $search = trim((string) $this->filters['search']);
            $query->where(function (Builder $nested) use ($search) {
                $nested->where('books.title', 'like', "%{$search}%")
                    ->orWhere('books.author', 'like', "%{$search}%")
                    ->orWhere('books.isbn', 'like', "%{$search}%");
            });
        }

        if (isset($this->filters['min_price']) && $this->filters['min_price'] !== '') {
            $query->where('books.price', '>=', (float) $this->filters['min_price']);
        }

        if (isset($this->filters['max_price']) && $this->filters['max_price'] !== '') {
            $query->where('books.price', '<=', (float) $this->filters['max_price']);
        }

        if (! empty($this->filters['stock_status'])) {
            if ($this->filters['stock_status'] === 'in_stock') {
                $query->where('books.stock_quantity', '>', 0);
            }

            if ($this->filters['stock_status'] === 'out_of_stock') {
                $query->where('books.stock_quantity', '<=', 0);
            }
        }

        // Ordering is strictly required for Laravel Excel's chunk reader to work properly
        return $query->orderBy('books.id');
    }

    /**
     * Map each row's columns before rendering it to excel.
     */
    public function map($book): array
    {
        $mapped = [];
        foreach ($this->columns() as $column) {
            $mapped[] = match ($column) {
                'id' => $book->id,
                'category_id' => $book->category_id,
                'category_name' => $book->joined_category_name ?? 'Uncategorized',
                'title' => $book->title,
                'author' => $book->author,
                'isbn' => $book->isbn,
                'price' => $book->price,
                'stock_quantity' => $book->stock_quantity,
                // Raw string from Query Builder, no Carbon instantiation needed
                'created_at' => $book->created_at, 
                'updated_at' => $book->updated_at,
                default => null,
            };
        }

        return $mapped;
    }

    /**
     * Define the excel file headings.
     */
    public function headings(): array
    {
        return array_map(
            fn (string $column) => self::AVAILABLE_COLUMNS[$column],
            $this->columns()
        );
    }

    /**
     * Read the database in chunks of 5000 for better throughput.
     */
    public function chunkSize(): int
    {
        return 5000;
    }

    public static function availableColumns(): array
    {
        return self::AVAILABLE_COLUMNS;
    }

    public static function normalizeColumns(array $requestedColumns): array
    {
        $normalized = array_values(array_filter($requestedColumns, fn ($column) => array_key_exists($column, self::AVAILABLE_COLUMNS)));

        return $normalized !== [] ? $normalized : array_keys(self::AVAILABLE_COLUMNS);
    }

    private function columns(): array
    {
        return self::normalizeColumns($this->selectedColumns);
    }
}