<?php

namespace App\Exports;

use App\Models\ReadingHistory;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReadingHistoryExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(private readonly int $userId)
    {
    }

    public function query(): Builder
    {
        return ReadingHistory::query()
            ->where('user_id', $this->userId)
            ->with(['book', 'order'])
            ->orderByDesc('last_seen_at')
            ->orderByDesc('created_at');
    }

    public function map($history): array
    {
        return [
            ucfirst((string) $history->event_type),
            $history->book?->title,
            $history->book?->author,
            $history->order_id,
            $history->quantity,
            $history->last_seen_at?->format('Y-m-d H:i:s'),
            $history->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            'Event Type',
            'Book Title',
            'Book Author',
            'Order ID',
            'Quantity',
            'Last Seen At',
            'Created At',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}