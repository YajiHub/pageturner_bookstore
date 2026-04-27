<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    use Exportable;

    private const AVAILABLE_COLUMNS = [
        'id' => 'Order ID',
        'customer_name' => 'Customer Name',
        'customer_email' => 'Customer Email',
        'status' => 'Status',
        'total_amount' => 'Total Amount',
        'address' => 'Shipping Address',
        'items_count' => 'Items Count',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ];

    public function __construct(
        private readonly array $filters = [],
        private readonly array $selectedColumns = []
    ) {
    }

    public function query(): Builder
    {
        $query = Order::query()->with(['user', 'orderItems']);

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['from_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['from_date']);
        }

        if (! empty($this->filters['to_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['to_date']);
        }

        if (! empty($this->filters['search'])) {
            $search = trim((string) $this->filters['search']);
            $query->where(function (Builder $nested) use ($search) {
                $nested->where('id', $search)
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderByDesc('created_at');
    }

    public function map($order): array
    {
        $mapped = [];

        foreach ($this->columns() as $column) {
            $mapped[] = match ($column) {
                'id' => $order->id,
                'customer_name' => $order->user?->name,
                'customer_email' => $order->user?->email,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'address' => $order->address,
                'items_count' => $order->orderItems->count(),
                'created_at' => $order->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $order->updated_at?->format('Y-m-d H:i:s'),
                default => null,
            };
        }

        return $mapped;
    }

    public function headings(): array
    {
        return array_map(
            fn (string $column) => self::AVAILABLE_COLUMNS[$column],
            $this->columns()
        );
    }

    public function chunkSize(): int
    {
        return 1000;
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
