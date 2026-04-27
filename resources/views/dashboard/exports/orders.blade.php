<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 20px; margin-bottom: 8px; }
        .muted { color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 8px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
    </style>
</head>
<body>
    <h1>Order History</h1>
    <div class="muted">Generated at {{ now()->format('Y-m-d H:i:s') }}</div>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Status</th>
                <th>Total Amount</th>
                <th>Items</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
                <tr>
                    <td>#{{ $order->id }}</td>
                    <td>{{ ucfirst($order->status) }}</td>
                    <td>₱{{ number_format($order->total_amount, 2) }}</td>
                    <td>
                        @foreach($order->orderItems as $item)
                            <div>{{ $item->book?->title ?? 'Unknown' }} x{{ $item->quantity }}</div>
                        @endforeach
                    </td>
                    <td>{{ $order->created_at?->format('Y-m-d H:i:s') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>