<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomerOrdersExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(private readonly int $userId)
    {
    }

    public function query(): Builder
    {
        return Order::query()
            ->where('user_id', $this->userId)
            ->with(['orderItems.book'])
            ->orderByDesc('created_at');
    }

    public function map($order): array
    {
        return [
            $order->id,
            $order->status,
            $order->total_amount,
            $order->address,
            $order->orderItems->count(),
            $order->orderItems->map(function ($item) {
                return ($item->book?->title ?? 'Unknown').' x'.$item->quantity;
            })->implode('; '),
            $order->created_at?->format('Y-m-d H:i:s'),
            $order->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Status',
            'Total Amount',
            'Shipping Address',
            'Items Count',
            'Items Summary',
            'Created At',
            'Updated At',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}