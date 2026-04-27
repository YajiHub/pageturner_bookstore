<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class NormalizeInputPayload
{
    public function handle(Request $request, Closure $next): Response
    {
        $normalized = $this->normalizeArray($request->all());
        $request->merge($normalized);

        return $next($request);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeArray(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $result[$key] = $this->normalizeValue($key, $value);
        }

        return $result;
    }

    private function normalizeValue(string $key, mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->normalizeArray($value);
        }

        if ($value instanceof UploadedFile) {
            return $value;
        }

        if (! is_string($value)) {
            return $value;
        }

        // Keep credential and token fields untouched to avoid altering secrets.
        if ($this->isSensitiveKey($key)) {
            return $value;
        }

        $trimmed = trim($value);
        $collapsed = preg_replace('/\s+/', ' ', $trimmed) ?? $trimmed;

        return $collapsed === '' ? null : $collapsed;
    }

    private function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);

        return str_contains($key, 'password')
            || str_contains($key, 'token')
            || str_contains($key, 'secret')
            || str_contains($key, 'code');
    }
}
