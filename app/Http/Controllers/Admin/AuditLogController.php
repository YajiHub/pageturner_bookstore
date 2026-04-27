<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');

        return view('admin.audit-logs.show', compact('auditLog'));
    }

    public function index(Request $request)
    {
        $query = $this->buildQuery($request);

        $auditLogs = $query->paginate(30)->withQueryString();

        return view('admin.audit-logs.index', compact('auditLogs'));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->buildQuery($request);

        $filename = 'audit_logs_'.now()->format('Y_m_d_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['id', 'created_at', 'actor', 'actor_role', 'action', 'event_category', 'severity', 'outcome', 'risk_score', 'is_suspicious', 'detection_tags', 'source', 'ip_address', 'request_method', 'request_url', 'target', 'description', 'archived_at', 'checksum']);

            $query->chunkById(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->created_at?->format('Y-m-d H:i:s'),
                        $log->user?->email ?? 'system',
                        $log->actor_role,
                        $log->action,
                        $log->event_category,
                        $log->severity,
                        $log->outcome,
                        $log->risk_score,
                        $log->is_suspicious ? 'yes' : 'no',
                        $log->detection_tags ? json_encode($log->detection_tags, JSON_UNESCAPED_SLASHES) : null,
                        $log->request_source,
                        $log->ip_address,
                        $log->request_method,
                        $log->request_url,
                        $log->auditable_type ? class_basename($log->auditable_type).'#'.$log->auditable_id : '-',
                        $log->description,
                        $log->archived_at?->format('Y-m-d H:i:s'),
                        $log->checksum,
                    ]);
                }
            }, 'id');

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function archive(Request $request)
    {
        if (! $this->supportsArchiveColumn()) {
            return redirect()->route('admin.audit-logs.index', $request->query())
                ->with('error', 'Audit archive column is not available yet. Run pending migrations first.');
        }

        $query = $this->buildQuery($request)->whereNull('archived_at');
        $count = (clone $query)->count();

        $query->update(['archived_at' => now()]);

        return redirect()->route('admin.audit-logs.index', $request->query())
            ->with('success', "Archived {$count} audit log entries.");
    }

    public function verifyIntegrity(Request $request)
    {
        if (! $this->supportsChecksumColumns()) {
            return redirect()->route('admin.audit-logs.index', $request->query())
                ->with('error', 'Audit checksum columns are not available yet. Run pending migrations first.');
        }

        $logs = $this->buildQuery($request)
            ->orderBy('id')
            ->get();

        $previousChecksum = null;
        $invalidCount = 0;

        foreach ($logs as $log) {
            $expectedChecksum = hash('sha256', json_encode($this->normalizeValue([
                'action' => $log->action,
                'auditable_type' => $log->auditable_type,
                'auditable_id' => $log->auditable_id,
                'description' => $log->description,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'ip_address' => $log->ip_address,
                'request_method' => $log->request_method,
                'request_url' => $log->request_url,
                'event_category' => $log->event_category,
                'severity' => $log->severity,
                'outcome' => $log->outcome,
                'risk_score' => $log->risk_score,
                'is_suspicious' => $log->is_suspicious,
                'request_source' => $log->request_source,
                'correlation_id' => $log->correlation_id,
                'request_id' => $log->request_id,
                'session_id' => $log->session_id,
                'actor_type' => $log->actor_type,
                'actor_identifier' => $log->actor_identifier,
                'actor_role' => $log->actor_role,
                'target_identifier' => $log->target_identifier,
                'detection_tags' => $log->detection_tags,
                'created_at' => (string) $log->created_at,
                'previous_checksum' => $previousChecksum,
            ]), JSON_UNESCAPED_SLASHES));

            if ($log->previous_checksum !== $previousChecksum || $log->checksum !== $expectedChecksum) {
                $invalidCount++;
            }

            $previousChecksum = $log->checksum;
        }

        $message = $invalidCount === 0
            ? 'Audit integrity check passed. No broken checksum chain entries found.'
            : "Audit integrity check found {$invalidCount} invalid entries.";

        return redirect()->route('admin.audit-logs.index', $request->query())
            ->with($invalidCount === 0 ? 'success' : 'error', $message);
    }

    private function buildQuery(Request $request)
    {
        $query = AuditLog::with('user')->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->input('action').'%');
        }

        if ($request->filled('actor')) {
            $actor = mb_strtolower($request->input('actor'));
            $query->whereHas('user', function ($q) use ($actor) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$actor}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$actor}%"]);
            });
        }

        if ($request->filled('severity') && $this->supportsColumn('severity')) {
            $query->where('severity', $request->string('severity')->toString());
        }

        if ($request->filled('outcome') && $this->supportsColumn('outcome')) {
            $query->where('outcome', $request->string('outcome')->toString());
        }

        if ($request->filled('suspicious') && $this->supportsColumn('is_suspicious')) {
            if ($request->input('suspicious') === 'yes') {
                $query->where('is_suspicious', true);
            }

            if ($request->input('suspicious') === 'no') {
                $query->where('is_suspicious', false);
            }
        }

        if ($request->filled('category') && $this->supportsColumn('event_category')) {
            $query->where('event_category', $request->string('category')->toString());
        }

        if ($request->filled('archived') && $this->supportsArchiveColumn()) {
            if ($request->input('archived') === 'yes') {
                $query->whereNotNull('archived_at');
            }

            if ($request->input('archived') === 'no') {
                $query->whereNull('archived_at');
            }
        }

        return $query;
    }

    private function supportsChecksumColumns(): bool
    {
        return Schema::hasTable('audit_logs')
            && Schema::hasColumn('audit_logs', 'checksum')
            && Schema::hasColumn('audit_logs', 'previous_checksum');
    }

    private function supportsArchiveColumn(): bool
    {
        return Schema::hasTable('audit_logs')
            && Schema::hasColumn('audit_logs', 'archived_at');
    }

    private function supportsColumn(string $column): bool
    {
        return Schema::hasTable('audit_logs')
            && Schema::hasColumn('audit_logs', $column);
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $keys = array_keys($value);
        $isList = $keys === array_keys($keys);

        if ($isList) {
            return array_map(fn ($item) => $this->normalizeValue($item), $value);
        }

        ksort($value);

        $normalized = [];
        foreach ($value as $key => $item) {
            $normalized[$key] = $this->normalizeValue($item);
        }

        return $normalized;
    }
}
