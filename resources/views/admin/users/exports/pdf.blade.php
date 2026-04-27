<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users Export</title>
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
    <h2>Users Export</h2>
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
            @forelse($users as $user)
                <tr>
                    @foreach($columns as $column)
                        <td>
                            @switch($column)
                                @case('email')
                                    @if(($privacyOptions['redact_email'] ?? false) && $user->email)
                                        @php
                                            [$local, $domain] = array_pad(explode('@', $user->email, 2), 2, '');
                                            $masked = $local !== '' && $domain !== '' ? mb_substr($local, 0, 2).str_repeat('*', max(1, mb_strlen($local) - 2)).'@'.$domain : '***';
                                        @endphp
                                        {{ $masked }}
                                    @else
                                        {{ $user->email }}
                                    @endif
                                    @break
                                @case('address')
                                    @if(($privacyOptions['redact_address'] ?? false) && $user->address)
                                        @php
                                            $trimmed = trim($user->address);
                                            $length = mb_strlen($trimmed);
                                            $masked = $length <= 6
                                                ? str_repeat('*', $length)
                                                : mb_substr($trimmed, 0, 3).str_repeat('*', max(1, $length - 6)).mb_substr($trimmed, -3);
                                        @endphp
                                        {{ $masked }}
                                    @else
                                        {{ $user->address }}
                                    @endif
                                    @break
                                @case('email_verified_at')
                                    {{ $user->email_verified_at?->format('Y-m-d H:i:s') }}
                                    @break
                                @case('created_at')
                                    {{ $user->created_at?->format('Y-m-d H:i:s') }}
                                    @break
                                @case('updated_at')
                                    {{ $user->updated_at?->format('Y-m-d H:i:s') }}
                                    @break
                                @default
                                    {{ $user->{$column} }}
                            @endswitch
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}">No matching users found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
