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
    <h1>Reading History</h1>
    <div class="muted">Generated at {{ now()->format('Y-m-d H:i:s') }}</div>
    <table>
        <thead>
            <tr>
                <th>Event</th>
                <th>Book</th>
                <th>Order ID</th>
                <th>Quantity</th>
                <th>Last Seen At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($history as $entry)
                <tr>
                    <td>{{ ucfirst($entry->event_type) }}</td>
                    <td>{{ $entry->book?->title ?? 'Unknown' }}</td>
                    <td>{{ $entry->order_id ? '#'.$entry->order_id : '-' }}</td>
                    <td>{{ $entry->quantity }}</td>
                    <td>{{ $entry->last_seen_at?->format('Y-m-d H:i:s') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>