<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Orders Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { margin: 0 0 8px 0; }
        .meta { margin-bottom: 12px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h2>Orders Export</h2>
    <div class="meta">Generated at: {{ now()->format('Y-m-d H:i:s') }}</div>

    <table>
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th>{{ $headings[$column] ?? $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    @foreach($columns as $column)
                        <td>
                            @switch($column)
                                @case('customer_name')
                                    {{ $order->user?->name }}
                                    @break
                                @case('customer_email')
                                    {{ $order->user?->email }}
                                    @break
                                @case('items_count')
                                    {{ $order->orderItems->count() }}
                                    @break
                                @case('created_at')
                                    {{ $order->created_at?->format('Y-m-d H:i:s') }}
                                    @break
                                @case('updated_at')
                                    {{ $order->updated_at?->format('Y-m-d H:i:s') }}
                                    @break
                                @default
                                    {{ $order->{$column} }}
                            @endswitch
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}">No matching orders found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
