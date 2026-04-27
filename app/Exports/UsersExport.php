<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    use Exportable;

    private const AVAILABLE_COLUMNS = [
        'id' => 'User ID',
        'name' => 'Name',
        'email' => 'Email',
        'role' => 'Role',
        'address' => 'Address',
        'email_verified_at' => 'Email Verified At',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ];

    public function __construct(
        private readonly array $filters = [],
        private readonly array $selectedColumns = [],
        private readonly array $privacyOptions = []
    ) {
    }

    public function query(): Builder
    {
        $query = User::query();

        if (! empty($this->filters['role'])) {
            $query->where('role', $this->filters['role']);
        }

        if (! empty($this->filters['search'])) {
            $search = trim((string) $this->filters['search']);
            $query->where(function (Builder $nested) use ($search) {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($this->filters['from_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['from_date']);
        }

        if (! empty($this->filters['to_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['to_date']);
        }

        return $query->orderByDesc('created_at');
    }

    public function map($user): array
    {
        $mapped = [];

        foreach ($this->columns() as $column) {
            $mapped[] = match ($column) {
                'id' => $user->id,
                'name' => $user->name,
                'email' => $this->redactEmail($user->email),
                'role' => $user->role,
                'address' => $this->redactAddress($user->address),
                'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i:s'),
                'created_at' => $user->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $user->updated_at?->format('Y-m-d H:i:s'),
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

    private function redactEmail(?string $email): ?string
    {
        if (! ($this->privacyOptions['redact_email'] ?? false) || ! $email) {
            return $email;
        }

        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') {
            return '***';
        }

        $visible = mb_substr($local, 0, 2);

        return $visible.str_repeat('*', max(1, mb_strlen($local) - 2)).'@'.$domain;
    }

    private function redactAddress(?string $address): ?string
    {
        if (! ($this->privacyOptions['redact_address'] ?? false) || ! $address) {
            return $address;
        }

        $trimmed = trim($address);
        $length = mb_strlen($trimmed);

        if ($length <= 6) {
            return str_repeat('*', $length);
        }

        return mb_substr($trimmed, 0, 3).str_repeat('*', max(1, $length - 6)).mb_substr($trimmed, -3);
    }
}
