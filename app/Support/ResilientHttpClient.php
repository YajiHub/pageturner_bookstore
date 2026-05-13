<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * HTTP Client Helper with built-in resilience for network failures
 * Provides timeout, retry, and connection error handling
 */
class ResilientHttpClient
{
    const DEFAULT_TIMEOUT = 30;
    const DEFAULT_CONNECT_TIMEOUT = 10;
    const MAX_RETRIES = 2;

    /**
     * Make a resilient HTTP GET request
     */
    public static function get(string $url, array $headers = [], array $query = []): ?Response
    {
        return self::makeRequest('GET', $url, $headers, $query, null);
    }

    /**
     * Make a resilient HTTP POST request
     */
    public static function post(string $url, array $data = [], array $headers = []): ?Response
    {
        return self::makeRequest('POST', $url, $headers, [], $data);
    }

    /**
     * Make a resilient HTTP request with retry logic
     */
    private static function makeRequest(
        string $method,
        string $url,
        array $headers = [],
        array $query = [],
        ?array $data = null,
        int $attempt = 1
    ): ?Response {
        try {
            $client = Http::timeout(self::DEFAULT_TIMEOUT)
                ->connectTimeout(self::DEFAULT_CONNECT_TIMEOUT)
                ->withHeaders($headers);

            $response = match ($method) {
                'GET' => $client->get($url, $query),
                'POST' => $client->post($url, $data ?? []),
                'PUT' => $client->put($url, $data ?? []),
                'DELETE' => $client->delete($url),
                default => throw new Exception("Unsupported HTTP method: {$method}"),
            };

            return $response;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            if ($attempt < self::MAX_RETRIES && self::isTransientError($e)) {
                Log::info("Retrying {$method} {$url} (attempt {$attempt}/" . self::MAX_RETRIES . ")");
                usleep(500000 * $attempt); // Exponential backoff
                return self::makeRequest($method, $url, $headers, $query, $data, $attempt + 1);
            }

            Log::error("HTTP connection failed: {$method} {$url}", [
                'error' => $e->getMessage(),
                'attempt' => $attempt,
            ]);

            throw new Exception("Network connection error. Please check your internet connection and try again.");
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error("HTTP request failed: {$method} {$url}", [
                'error' => $e->getMessage(),
                'status' => $e->response?->status(),
            ]);

            throw new Exception("Server error. Please try again later.");
        } catch (Exception $e) {
            Log::error("HTTP request error: {$method} {$url}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if error is transient (worth retrying)
     */
    private static function isTransientError(Exception $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'timeout')
            || str_contains($message, 'connection refused')
            || str_contains($message, 'network unreachable')
            || str_contains($message, 'temporary failure')
            || str_contains($message, 'connection reset');
    }
}
