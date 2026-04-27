<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Books Export</title>
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
    <h2>Books Export</h2>
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
            @forelse($books as $book)
                <tr>
                    @foreach($columns as $column)
                        <td>
                            @switch($column)
                                @case('category_name')
                                    {{ $book->category->name ?? 'Uncategorized' }}
                                    @break
                                @case('created_at')
                                    {{ $book->created_at?->format('Y-m-d H:i:s') }}
                                    @break
                                @case('updated_at')
                                    {{ $book->updated_at?->format('Y-m-d H:i:s') }}
                                    @break
                                @default
                                    {{ $book->{$column} }}
                            @endswitch
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}">No matching books found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
