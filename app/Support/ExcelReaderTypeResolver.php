<?php

namespace App\Support;

use Maatwebsite\Excel\Excel as ExcelReader;

class ExcelReaderTypeResolver
{
    public static function fromFilename(?string $filename): ?string
    {
        if (! is_string($filename) || trim($filename) === '') {
            return null;
        }

        $extension = mb_strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv' => ExcelReader::CSV,
            'xls' => ExcelReader::XLS,
            'xlsx' => ExcelReader::XLSX,
            default => null,
        };
    }
}
