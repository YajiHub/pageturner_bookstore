<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\AuditCriticalAlertNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AuditLogger
{
    /**
     * Persist a single audit entry with optional model and before/after diffs.
     */
    public static function log(
        string $action,
        mixed $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $description = null,
        ?int $userId = null
    ): AuditLog {
        $request = request();
        $target = $auditable instanceof Model ? $auditable : null;
        $supportsChecksum = self::supportsChecksumColumns();
        $actor = Auth::user();
        $eventCategory = self::resolveCategory($action);
        $severity = self::resolveSeverity($action);
        $outcome = self::resolveOutcome($action);
        $riskScore = self::resolveRiskScore($action, $severity, $outcome);
        $detectionTags = self::resolveDetectionTags($action, $severity, $outcome, $request?->ip());
        $isSuspicious = $riskScore >= 70 || in_array('auth_failure', $detectionTags, true) || in_array('privilege_change', $detectionTags, true);
        $requestSource = self::resolveRequestSource($request?->path());
        $correlationId = self::resolveHeaderValue($request, ['X-Correlation-ID', 'X-Request-ID']);
        $requestId = self::resolveHeaderValue($request, ['X-Request-ID', 'X-Correlation-ID']);
        $sessionId = $request && method_exists($request, 'hasSession') && $request->hasSession() ? $request->session()->getId() : null;

        $payload = [
            'user_id' => $userId ?? Auth::id(),
            'actor_type' => $actor ? 'user' : 'system',
            'actor_identifier' => $actor?->email ?? ($actor?->name ?? 'system'),
            'actor_role' => $actor?->role,
            'action' => $action,
            'event_category' => $eventCategory,
            'auditable_type' => $target ? $target::class : null,
            'auditable_id' => $target?->getKey(),
            'target_identifier' => $target ? class_basename($target).'#'.$target->getKey() : null,
            'description' => $description,
            'severity' => $severity,
            'outcome' => $outcome,
            'risk_score' => $riskScore,
            'is_suspicious' => $isSuspicious,
            'detection_tags' => $detectionTags === [] ? null : $detectionTags,
            'old_values' => empty($oldValues) ? null : $oldValues,
            'new_values' => empty($newValues) ? null : $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'request_url' => $request?->fullUrl(),
            'request_method' => $request?->method(),
            'http_status_code' => null,
            'request_source' => $requestSource,
            'correlation_id' => $correlationId,
            'request_id' => $requestId,
            'session_id' => $sessionId,
            'created_at' => now(),
        ];

        $payload = self::filterPayloadToExistingColumns($payload);

        if ($supportsChecksum) {
            $previousChecksum = AuditLog::query()->latest('id')->value('checksum');
            $payload['previous_checksum'] = $previousChecksum;
            $payload['checksum'] = self::generateChecksum($payload, $previousChecksum);
        }

        $auditLog = AuditLog::create($payload);

        if (self::isCriticalAction($action)) {
            User::query()->where('role', 'admin')->each(function (User $admin) use ($auditLog) {
                try {
                    $admin->notify(new AuditCriticalAlertNotification($auditLog));
                } catch (Throwable $exception) {
                    Log::warning('Audit critical alert notification failed.', [
                        'audit_log_id' => $auditLog->id,
                        'admin_user_id' => $admin->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });
        }

        return $auditLog;
    }

    private static function supportsChecksumColumns(): bool
    {
        return Schema::hasTable('audit_logs')
            && Schema::hasColumn('audit_logs', 'checksum')
            && Schema::hasColumn('audit_logs', 'previous_checksum');
    }

    private static function generateChecksum(array $payload, ?string $previousChecksum): string
    {
        $checksumPayload = [
            'action' => $payload['action'] ?? null,
            'auditable_type' => $payload['auditable_type'] ?? null,
            'auditable_id' => $payload['auditable_id'] ?? null,
            'description' => $payload['description'] ?? null,
            'old_values' => $payload['old_values'] ?? null,
            'new_values' => $payload['new_values'] ?? null,
            'ip_address' => $payload['ip_address'] ?? null,
            'request_method' => $payload['request_method'] ?? null,
            'request_url' => $payload['request_url'] ?? null,
            'event_category' => $payload['event_category'] ?? null,
            'severity' => $payload['severity'] ?? null,
            'outcome' => $payload['outcome'] ?? null,
            'risk_score' => $payload['risk_score'] ?? null,
            'is_suspicious' => $payload['is_suspicious'] ?? null,
            'request_source' => $payload['request_source'] ?? null,
            'correlation_id' => $payload['correlation_id'] ?? null,
            'request_id' => $payload['request_id'] ?? null,
            'session_id' => $payload['session_id'] ?? null,
            'actor_type' => $payload['actor_type'] ?? null,
            'actor_identifier' => $payload['actor_identifier'] ?? null,
            'actor_role' => $payload['actor_role'] ?? null,
            'target_identifier' => $payload['target_identifier'] ?? null,
            'detection_tags' => $payload['detection_tags'] ?? null,
            'created_at' => (string) ($payload['created_at'] ?? ''),
            'previous_checksum' => $previousChecksum,
        ];

        $normalized = self::normalizeValue($checksumPayload);

        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_SLASHES));
    }

    private static function normalizeValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $keys = array_keys($value);
        $isList = $keys === array_keys($keys);

        if ($isList) {
            return array_map(fn ($item) => self::normalizeValue($item), $value);
        }

        ksort($value);

        $normalized = [];
        foreach ($value as $key => $item) {
            $normalized[$key] = self::normalizeValue($item);
        }

        return $normalized;
    }

    private static function isCriticalAction(string $action): bool
    {
        $criticalPatterns = [
            'deleted',
            'maintenance.window_start',
            'maintenance.window_started',
            'order.status_updated',
            'transfer.users_export',
            'transfer.users_import',
            'two_factor',
        ];

        foreach ($criticalPatterns as $pattern) {
            if (str_contains($action, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private static function resolveCategory(string $action): string
    {
        if (str_starts_with($action, 'auth.')) {
            return 'authentication';
        }

        if (str_starts_with($action, 'transfer.')) {
            return 'data_transfer';
        }

        if (str_starts_with($action, 'order.')) {
            return 'order_management';
        }

        if (str_starts_with($action, 'book.') || str_starts_with($action, 'category.')) {
            return 'catalog_management';
        }

        if (str_starts_with($action, 'maintenance.')) {
            return 'operations';
        }

        return 'application';
    }

    private static function resolveSeverity(string $action): string
    {
        $criticalPatterns = ['deleted', 'maintenance.window_start', 'transfer.users_', 'two_factor'];
        foreach ($criticalPatterns as $pattern) {
            if (str_contains($action, $pattern)) {
                return 'critical';
            }
        }

        $highPatterns = ['failed', 'denied', 'forbidden', 'suspended', 'cancelled'];
        foreach ($highPatterns as $pattern) {
            if (str_contains($action, $pattern)) {
                return 'high';
            }
        }

        $mediumPatterns = ['updated', 'status_updated', 'import', 'export', 'verify'];
        foreach ($mediumPatterns as $pattern) {
            if (str_contains($action, $pattern)) {
                return 'medium';
            }
        }

        return 'info';
    }

    private static function resolveOutcome(string $action): string
    {
        $failurePatterns = ['failed', 'denied', 'forbidden', 'invalid', 'error'];
        foreach ($failurePatterns as $pattern) {
            if (str_contains($action, $pattern)) {
                return 'failure';
            }
        }

        return 'success';
    }

    private static function resolveRiskScore(string $action, string $severity, string $outcome): int
    {
        $base = match ($severity) {
            'critical' => 85,
            'high' => 65,
            'medium' => 35,
            default => 15,
        };

        if ($outcome === 'failure') {
            $base += 10;
        }

        if (str_contains($action, 'auth.') || str_contains($action, 'two_factor')) {
            $base += 10;
        }

        return min(100, max(0, $base));
    }

    private static function resolveDetectionTags(string $action, string $severity, string $outcome, ?string $ipAddress): array
    {
        $tags = [];

        if ($outcome === 'failure') {
            $tags[] = 'operation_failure';
        }

        if (str_contains($action, 'auth.') || str_contains($action, 'login')) {
            $tags[] = 'auth_event';
        }

        if (str_contains($action, 'failed') || str_contains($action, 'denied')) {
            $tags[] = 'auth_failure';
        }

        if (str_contains($action, 'role') || str_contains($action, 'permission') || str_contains($action, 'two_factor')) {
            $tags[] = 'privilege_change';
        }

        if ($severity === 'critical') {
            $tags[] = 'critical_event';
        }

        if (is_string($ipAddress) && self::isPrivateIp($ipAddress)) {
            $tags[] = 'private_network';
        }

        return array_values(array_unique($tags));
    }

    private static function resolveRequestSource(?string $path): string
    {
        if (app()->runningInConsole()) {
            return 'cli';
        }

        if (is_string($path) && str_starts_with($path, 'api/')) {
            return 'api';
        }

        return 'web';
    }

    private static function resolveHeaderValue($request, array $keys): ?string
    {
        if (! $request) {
            return null;
        }

        foreach ($keys as $key) {
            $value = $request->header($key);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private static function isPrivateIp(string $ipAddress): bool
    {
        return ! filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    private static function filterPayloadToExistingColumns(array $payload): array
    {
        if (! Schema::hasTable('audit_logs')) {
            return $payload;
        }

        $columns = Schema::getColumnListing('audit_logs');

        return Arr::only($payload, $columns);
    }
}
