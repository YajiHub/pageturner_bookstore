@extends('layouts.app')

@section('title', 'Audit Log Entry')

@section('header')
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Audit Log Entry #{{ $auditLog->id }}</h2>
        <a href="{{ route('admin.audit-logs.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Back to Audit Logs</a>
    </div>
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><span class="font-semibold text-gray-700">Time:</span> {{ $auditLog->created_at?->format('Y-m-d H:i:s') }}</div>
            <div><span class="font-semibold text-gray-700">Actor:</span> {{ $auditLog->user?->email ?? 'System' }}</div>
            <div><span class="font-semibold text-gray-700">Actor Type:</span> {{ $auditLog->actor_type ?? '-' }}</div>
            <div><span class="font-semibold text-gray-700">Actor Role:</span> {{ $auditLog->actor_role ?? '-' }}</div>
            <div><span class="font-semibold text-gray-700">Action:</span> {{ $auditLog->action }}</div>
            <div><span class="font-semibold text-gray-700">Category:</span> {{ $auditLog->event_category ?? '-' }}</div>
            <div><span class="font-semibold text-gray-700">Severity:</span> {{ strtoupper($auditLog->severity ?? 'info') }}</div>
            <div><span class="font-semibold text-gray-700">Outcome:</span> {{ strtoupper($auditLog->outcome ?? 'success') }}</div>
            <div><span class="font-semibold text-gray-700">Risk Score:</span> {{ (int) ($auditLog->risk_score ?? 0) }}/100</div>
            <div><span class="font-semibold text-gray-700">Suspicious:</span> {{ $auditLog->is_suspicious ? 'Yes' : 'No' }}</div>
            <div><span class="font-semibold text-gray-700">Target:</span> {{ $auditLog->auditable_type ? class_basename($auditLog->auditable_type).'#'.$auditLog->auditable_id : '-' }}</div>
            <div><span class="font-semibold text-gray-700">Target Identifier:</span> {{ $auditLog->target_identifier ?? '-' }}</div>
            <div><span class="font-semibold text-gray-700">Request Source:</span> {{ $auditLog->request_source ?? '-' }}</div>
            <div><span class="font-semibold text-gray-700">Method:</span> {{ $auditLog->request_method ?? '-' }}</div>
            <div><span class="font-semibold text-gray-700">IP Address:</span> {{ $auditLog->ip_address ?? '-' }}</div>
            <div><span class="font-semibold text-gray-700">Correlation ID:</span> <span class="font-mono break-all">{{ $auditLog->correlation_id ?? '-' }}</span></div>
            <div><span class="font-semibold text-gray-700">Request ID:</span> <span class="font-mono break-all">{{ $auditLog->request_id ?? '-' }}</span></div>
            <div><span class="font-semibold text-gray-700">Session ID:</span> <span class="font-mono break-all">{{ $auditLog->session_id ?? '-' }}</span></div>
            <div class="md:col-span-2"><span class="font-semibold text-gray-700">Request URL:</span> <span class="break-all">{{ $auditLog->request_url ?? '-' }}</span></div>
            <div class="md:col-span-2"><span class="font-semibold text-gray-700">Detection Tags:</span> {{ is_array($auditLog->detection_tags) && $auditLog->detection_tags !== [] ? implode(', ', $auditLog->detection_tags) : '-' }}</div>
            <div class="md:col-span-2"><span class="font-semibold text-gray-700">Description:</span> {{ $auditLog->description ?? '-' }}</div>
            <div class="md:col-span-2"><span class="font-semibold text-gray-700">Checksum:</span> <span class="font-mono break-all">{{ $auditLog->checksum ?? 'legacy record' }}</span></div>
            <div class="md:col-span-2"><span class="font-semibold text-gray-700">Previous Checksum:</span> <span class="font-mono break-all">{{ $auditLog->previous_checksum ?? '-' }}</span></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div>
                <h3 class="font-semibold text-gray-700 mb-2">Before</h3>
                <pre class="text-xs bg-gray-50 p-3 rounded overflow-auto">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '-' }}</pre>
            </div>
            <div>
                <h3 class="font-semibold text-gray-700 mb-2">After</h3>
                <pre class="text-xs bg-gray-50 p-3 rounded overflow-auto">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '-' }}</pre>
            </div>
        </div>
    </div>
@endsection
