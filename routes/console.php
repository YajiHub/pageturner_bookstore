<?php

use App\Models\AuditLog;
use App\Models\DataTransferJob;
use App\Models\Order;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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

// ===============================================================
// LAB 6 NEW AUTOMATED TASKS
// ===============================================================

Artisan::command('order:cleanup-pending', function () {
    $cutoff = now()->subHours(24);
    $count = Order::where('status', 'pending')->where('created_at', '<', $cutoff)->update(['status' => 'cancelled']);
    $this->info("Cancelled {$count} pending orders older than 24 hours.");
})->purpose('Cancel pending orders > 24 hours old');

Artisan::command('session:cleanup', function () {
    if (config('session.driver') === 'database' && Schema::hasTable(config('session.table'))) {
        $lifetime = config('session.lifetime') * 60;
        $deleted = DB::table(config('session.table'))->where('last_activity', '<', time() - $lifetime)->delete();
        $this->info("Cleared {$deleted} expired sessions from database.");
    } else {
        $this->info('Session driver is not database or table is missing. Handled by native garbage collection.');
    }
})->purpose('Clear expired sessions');

Artisan::command('log:rotate', function () {
    $logPath = storage_path('logs/laravel.log');
    if (file_exists($logPath)) {
        $archiveName = 'laravel-' . date('Y-m-d-His') . '.log';
        $archivePath = storage_path('logs/' . $archiveName);
        rename($logPath, $archivePath);

        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            $zipPath = $archivePath . '.zip';
            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                $zip->addFile($archivePath, $archiveName);
                $zip->close();
                unlink($archivePath); 
            }
        }
        $this->info('Rotated and compressed laravel.log');
    } else {
        $this->info('No laravel.log found to rotate.');
    }
})->purpose('Archive and compress old logs');

Artisan::command('report:generate-daily', function () {
    $yesterday = now()->subDay()->toDateString();
    $sales = Order::where('status', 'completed')->whereDate('updated_at', $yesterday)->sum('total_amount');
    $count = Order::where('status', 'completed')->whereDate('updated_at', $yesterday)->count();

    $admins = User::where('role', 'admin')->pluck('email')->toArray();
    if (!empty($admins)) {
        $formattedSales = number_format($sales, 2);
        Mail::raw("Daily Sales Report for {$yesterday}:\n\nTotal Completed Orders: {$count}\nTotal Revenue: Php {$formattedSales}", function ($message) use ($admins, $yesterday) {
            $message->to($admins)->subject("Daily Sales Report - {$yesterday}");
        });
    }
    $this->info("Daily sales report generated and emailed to admins.");
})->purpose('Generate daily sales report');

Artisan::command('notification:prune', function () {
    if (Schema::hasTable('notifications')) {
        $count = DB::table('notifications')->where('created_at', '<', now()->subDays(90))->delete();
        $this->info("Deleted {$count} old notifications.");
    } else {
        $this->info("Notifications table does not exist.");
    }
})->purpose('Delete old notification records > 90 days');

Artisan::command('audit:archive', function () {
    $cutoff = now()->subYear();
    $logs = AuditLog::where('created_at', '<', $cutoff)->get();
    
    if ($logs->count() > 0) {
        $csv = "id,action,user_id,created_at\n";
        foreach ($logs as $log) {
            $csv .= "{$log->id},{$log->action},{$log->user_id},{$log->created_at}\n";
        }
        Storage::disk('local')->put('archives/audit-logs-' . now()->format('Y-m-d') . '.csv', $csv);
        AuditLog::where('created_at', '<', $cutoff)->delete();
    }
    $this->info("Archived {$logs->count()} audit logs older than 1 year to CSV.");
})->purpose('Archive audit logs > 1 year old');


// ===============================================================
// LAB 6 SCHEDULER
// ===============================================================

Schedule::command('backup:run --only-db')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('backup:run --only-files')->dailyAt('01:30')->withoutOverlapping();
Schedule::command('backup:clean')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('backup:monitor')->hourly()->withoutOverlapping();

// Lab 6: Scheduled operational maintenance tasks (Rubric Requirements)
Schedule::command('order:cleanup-pending')->hourly()->withoutOverlapping();
Schedule::command('session:cleanup')->daily()->withoutOverlapping();
Schedule::command('log:rotate')->weekly()->withoutOverlapping();
Schedule::command('report:generate-daily')->dailyAt('06:00')->withoutOverlapping();
Schedule::command('notification:prune')->weekly()->withoutOverlapping();
Schedule::command('audit:archive')->monthly()->withoutOverlapping();

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