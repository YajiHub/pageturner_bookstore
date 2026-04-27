<?php

use App\Models\AuditLog;
use App\Models\DataTransferJob;
use App\Services\AuditLogger;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('maintenance:purge-audit-logs {--days=90}', function () {
    $days = max(1, (int) $this->option('days'));
    $cutoff = now()->subDays($days);

    $deleted = AuditLog::query()
        ->where('created_at', '<', $cutoff)
        ->delete();

    $this->info("Purged {$deleted} audit logs older than {$days} day(s).");
})->purpose('Delete old audit log records based on retention window.');

Artisan::command('maintenance:cleanup-transfer-files {--hours=48}', function () {
    $hours = max(1, (int) $this->option('hours'));
    $cutoff = now()->subHours($hours);

    $jobs = DataTransferJob::query()
        ->whereIn('status', ['completed', 'failed'])
        ->where('created_at', '<', $cutoff)
        ->get(['id', 'stored_path', 'result_path']);

    $deletedFiles = 0;

    foreach ($jobs as $job) {
        foreach ([$job->stored_path, $job->result_path] as $path) {
            if (! $path) {
                continue;
            }

            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
                $deletedFiles++;
            }
        }
    }

    $this->info("Deleted {$deletedFiles} stale transfer file(s) from ".count($jobs).' transfer job(s).');
})->purpose('Remove stale local import/export artifacts for completed/failed transfer jobs.');

Artisan::command('maintenance:window-start {--retry=60} {--refresh=15}', function () {
    $retry = max(1, (int) $this->option('retry'));
    $refresh = max(1, (int) $this->option('refresh'));
    $secret = env('MAINTENANCE_WINDOW_SECRET');

    $params = [
        '--retry' => $retry,
        '--refresh' => $refresh,
    ];

    if ($secret) {
        $params['--secret'] = $secret;
    }

    Artisan::call('down', $params);

    AuditLogger::log(
        action: 'maintenance.window_started',
        description: 'Scheduled maintenance window started by scheduler.',
        newValues: [
            'retry_seconds' => $retry,
            'refresh_seconds' => $refresh,
            'secret_enabled' => (bool) $secret,
        ]
    );

    $this->info('Application placed into maintenance mode.');
})->purpose('Start a controlled maintenance window with optional bypass secret.');

Artisan::command('maintenance:window-end', function () {
    Artisan::call('up');

    AuditLogger::log(
        action: 'maintenance.window_ended',
        description: 'Scheduled maintenance window ended by scheduler.'
    );

    $this->info('Application returned to live mode.');
})->purpose('End the maintenance window and resume normal traffic.');

Artisan::command('maintenance:scan-transfer-health {--stuck-minutes=90}', function () {
    $stuckMinutes = max(5, (int) $this->option('stuck-minutes'));
    $cutoff = now()->subMinutes($stuckMinutes);

    $stalledQueued = DataTransferJob::query()
        ->where('status', 'queued')
        ->where('created_at', '<', $cutoff)
        ->count();

    $stalledProcessing = DataTransferJob::query()
        ->where('status', 'processing')
        ->where(function ($q) use ($cutoff) {
            $q->where('started_at', '<', $cutoff)
                ->orWhere(function ($q2) use ($cutoff) {
                    $q2->whereNull('started_at')
                        ->where('created_at', '<', $cutoff);
                });
        })
        ->count();

    $failedToday = DataTransferJob::query()
        ->where('status', 'failed')
        ->whereDate('updated_at', now()->toDateString())
        ->count();

    $summary = [
        'stuck_minutes_threshold' => $stuckMinutes,
        'stalled_queued' => $stalledQueued,
        'stalled_processing' => $stalledProcessing,
        'failed_today' => $failedToday,
    ];

    if (($stalledQueued + $stalledProcessing) > 0) {
        AuditLogger::log(
            action: 'maintenance.transfer_health_alert',
            description: 'Detected stalled data transfer jobs during scheduled health scan.',
            newValues: $summary
        );
    }

    $this->info('Transfer health scan complete: '.json_encode($summary));
})->purpose('Detect stalled transfer jobs and record operational alerts.');

Artisan::command('maintenance:verify-audit-chain {--days=30}', function () {
    $days = max(1, (int) $this->option('days'));
    $cutoff = now()->subDays($days);

    $logs = AuditLog::query()
        ->where('created_at', '>=', $cutoff)
        ->orderBy('id')
        ->get();

    $previousChecksum = null;
    $invalidCount = 0;

    $normalizeValue = function (mixed $value) use (&$normalizeValue): mixed {
        if (! is_array($value)) {
            return $value;
        }

        $keys = array_keys($value);
        $isList = $keys === array_keys($keys);

        if ($isList) {
            return array_map(fn ($item) => $normalizeValue($item), $value);
        }

        ksort($value);

        $normalized = [];
        foreach ($value as $key => $item) {
            $normalized[$key] = $normalizeValue($item);
        }

        return $normalized;
    };

    foreach ($logs as $log) {
        $payload = [
            'action' => $log->action,
            'auditable_type' => $log->auditable_type,
            'auditable_id' => $log->auditable_id,
            'description' => $log->description,
            'old_values' => $log->old_values,
            'new_values' => $log->new_values,
            'ip_address' => $log->ip_address,
            'request_method' => $log->request_method,
            'request_url' => $log->request_url,
            'created_at' => (string) $log->created_at,
            'previous_checksum' => $previousChecksum,
        ];

        $expected = hash('sha256', json_encode($normalizeValue($payload), JSON_UNESCAPED_SLASHES));

        if ($log->previous_checksum !== $previousChecksum || $log->checksum !== $expected) {
            $invalidCount++;
        }

        $previousChecksum = $log->checksum;
    }

    if ($invalidCount > 0) {
        AuditLogger::log(
            action: 'maintenance.audit_integrity_failed',
            description: 'Scheduled audit checksum verification detected invalid entries.',
            newValues: [
                'window_days' => $days,
                'checked_entries' => $logs->count(),
                'invalid_entries' => $invalidCount,
            ]
        );
    }

    $this->info("Audit checksum verification complete. Invalid entries: {$invalidCount}");
})->purpose('Verify audit checksum chain for recent entries and log anomalies.');

// Lab 6: Automated backup lifecycle for enterprise data management.
Schedule::command('backup:run --only-db')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('backup:run --only-files')->dailyAt('01:30')->withoutOverlapping();
Schedule::command('backup:clean')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('backup:monitor')->hourly()->withoutOverlapping();

// Lab 6: Scheduled operational maintenance tasks.
Schedule::command('maintenance:cleanup-transfer-files --hours='.env('MAINTENANCE_TRANSFER_FILE_RETENTION_HOURS', 48))
    ->dailyAt('02:30')
    ->withoutOverlapping();

Schedule::command('maintenance:purge-audit-logs --days='.env('MAINTENANCE_AUDIT_RETENTION_DAYS', 90))
    ->dailyAt('03:00')
    ->withoutOverlapping();

Schedule::command('maintenance:verify-audit-chain --days='.env('MAINTENANCE_AUDIT_INTEGRITY_DAYS', 30))
    ->dailyAt('03:15')
    ->withoutOverlapping();

Schedule::command('maintenance:scan-transfer-health --stuck-minutes='.env('MAINTENANCE_STUCK_TRANSFER_MINUTES', 90))
    ->hourlyAt(20)
    ->withoutOverlapping();

Schedule::command('queue:prune-failed --hours=72')
    ->dailyAt('03:30')
    ->when(fn () => Schema::hasTable('failed_jobs'))
    ->withoutOverlapping();

$windowEnabled = filter_var(env('MAINTENANCE_WINDOW_ENABLED', false), FILTER_VALIDATE_BOOL);

Schedule::command('maintenance:window-start --retry='.env('MAINTENANCE_WINDOW_RETRY_SECONDS', 60).' --refresh='.env('MAINTENANCE_WINDOW_REFRESH_SECONDS', 15))
    ->weeklyOn((int) env('MAINTENANCE_WINDOW_DAY', 0), env('MAINTENANCE_WINDOW_START', '04:00'))
    ->when(fn () => $windowEnabled)
    ->withoutOverlapping();

Schedule::command('maintenance:window-end')
    ->weeklyOn((int) env('MAINTENANCE_WINDOW_DAY', 0), env('MAINTENANCE_WINDOW_END', '04:15'))
    ->when(fn () => $windowEnabled)
    ->withoutOverlapping();
