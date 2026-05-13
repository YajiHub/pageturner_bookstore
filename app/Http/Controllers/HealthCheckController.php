<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;
use Exception;

class HealthCheckController extends Controller
{
    /**
     * System health check endpoint
     * Returns status of database, cache, AI services, and storage
     */
    public function index(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'services' => [],
        ];

        // Check database
        $health['services']['database'] = $this->checkDatabase();

        // Check cache
        $health['services']['cache'] = $this->checkCache();

        // Check storage
        $health['services']['storage'] = $this->checkStorage();

        // Check AI service availability
        $health['services']['ai'] = $this->checkAIService();

        // Determine overall health
        $allHealthy = collect($health['services'])->every(fn($service) => $service['healthy'] === true);
        $health['status'] = $allHealthy ? 'healthy' : 'degraded';

        $statusCode = $allHealthy ? 200 : 503;

        return response()->json($health, $statusCode);
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);
            DB::statement('SELECT 1');
            $responseTime = (microtime(true) - $startTime) * 1000;

            return [
                'healthy' => true,
                'responseTime' => round($responseTime, 2) . 'ms',
                'status' => 'connected',
            ];
        } catch (Exception $e) {
            return [
                'healthy' => false,
                'status' => 'disconnected',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache connectivity
     */
    private function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . uniqid();
            $startTime = microtime(true);

            Cache::put($testKey, 'test', 60);
            $cached = Cache::get($testKey);
            Cache::forget($testKey);

            $responseTime = (microtime(true) - $startTime) * 1000;

            if ($cached !== 'test') {
                throw new Exception('Cache read/write verification failed');
            }

            return [
                'healthy' => true,
                'responseTime' => round($responseTime, 2) . 'ms',
                'driver' => config('cache.default'),
                'status' => 'connected',
            ];
        } catch (Exception $e) {
            return [
                'healthy' => false,
                'driver' => config('cache.default'),
                'status' => 'disconnected',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage accessibility
     */
    private function checkStorage(): array
    {
        try {
            $disk = \Storage::disk('public');
            $exists = $disk->exists('covers');

            return [
                'healthy' => $exists,
                'status' => $exists ? 'accessible' : 'not_found',
                'disk' => 'public',
                'covers' => $exists ? 'available' : 'missing',
            ];
        } catch (Exception $e) {
            return [
                'healthy' => false,
                'status' => 'error',
                'disk' => 'public',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check AI service availability
     */
    private function checkAIService(): array
    {
        try {
            $primary = config('ai.default_provider', 'gemini');
            $fallbackEnabled = config('ai.fallback_enabled', true);

            return [
                'healthy' => true,
                'primaryProvider' => $primary,
                'fallbackEnabled' => $fallbackEnabled,
                'status' => 'configured',
                'note' => 'Actual connectivity would require API call (not tested in health check)',
            ];
        } catch (Exception $e) {
            return [
                'healthy' => false,
                'status' => 'misconfigured',
                'error' => $e->getMessage(),
            ];
        }
    }
}
