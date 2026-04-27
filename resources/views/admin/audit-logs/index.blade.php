@extends('layouts.app')

@section('title', 'Audit Logs')

@section('header')
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Audit Logs</h2>
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Back to Dashboard</a>
    </div>
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow mb-6 p-6">
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="action" class="block text-sm font-medium text-gray-700 mb-1">Action</label>
                <input id="action" name="action" value="{{ request('action') }}" placeholder="e.g. order.status_updated" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="actor" class="block text-sm font-medium text-gray-700 mb-1">Actor</label>
                <input id="actor" name="actor" value="{{ request('actor') }}" placeholder="Name or email" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="archived" class="block text-sm font-medium text-gray-700 mb-1">Archived</label>
                <select id="archived" name="archived" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All</option>
                    <option value="no" @selected(request('archived') === 'no')>Active only</option>
                    <option value="yes" @selected(request('archived') === 'yes')>Archived only</option>
                </select>
            </div>
            </div>
            <details class="rounded-md border border-slate-200 bg-slate-50 p-3">
                <summary class="cursor-pointer text-sm font-medium text-slate-700">Advanced Security Filters</summary>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-3">
            <div>
                <label for="severity" class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
                <select id="severity" name="severity" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All</option>
                    <option value="info" @selected(request('severity') === 'info')>Info</option>
                    <option value="medium" @selected(request('severity') === 'medium')>Medium</option>
                    <option value="high" @selected(request('severity') === 'high')>High</option>
                    <option value="critical" @selected(request('severity') === 'critical')>Critical</option>
                </select>
            </div>
            <div>
                <label for="outcome" class="block text-sm font-medium text-gray-700 mb-1">Outcome</label>
                <select id="outcome" name="outcome" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All</option>
                    <option value="success" @selected(request('outcome') === 'success')>Success</option>
                    <option value="failure" @selected(request('outcome') === 'failure')>Failure</option>
                </select>
            </div>
            <div>
                <label for="suspicious" class="block text-sm font-medium text-gray-700 mb-1">Suspicious</label>
                <select id="suspicious" name="suspicious" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All</option>
                    <option value="yes" @selected(request('suspicious') === 'yes')>Yes</option>
                    <option value="no" @selected(request('suspicious') === 'no')>No</option>
                </select>
            </div>
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <input id="category" name="category" value="{{ request('category') }}" placeholder="e.g. authentication" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
                </div>
            </details>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 text-sm">Filter</button>
                <a href="{{ route('admin.audit-logs.index') }}" class="px-4 py-2 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 text-sm">Clear</a>
            </div>
        </form>
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.audit-logs.export', request()->query()) }}" class="px-4 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-700 text-sm">Export CSV</a>
            <form method="POST" action="{{ route('admin.audit-logs.archive', request()->query()) }}">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-md bg-amber-600 text-white hover:bg-amber-700 text-sm">Archive Filtered</button>
            </form>
            <form method="POST" action="{{ route('admin.audit-logs.verify-integrity', request()->query()) }}">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-md bg-slate-700 text-white hover:bg-slate-800 text-sm">Verify Integrity</button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actor</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Security</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Integrity</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Changes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($auditLogs as $log)
                        <tr class="align-top hover:bg-gray-50">
                            <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">
                                {{ $log->created_at?->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                {{ $log->user?->name ?? 'System' }}
                                @if($log->user?->email)
                                    <div class="text-xs text-gray-500">{{ $log->user->email }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex px-2 py-1 text-xs rounded bg-indigo-50 text-indigo-700 font-medium">{{ $log->action }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                @if($log->auditable_type)
                                    {{ class_basename($log->auditable_type) }}#{{ $log->auditable_id }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 max-w-xs">
                                {{ $log->description ?? '-' }}
                                <div class="mt-1 text-xs">
                                    @if($log->archived_at)
                                        <span class="inline-flex px-2 py-0.5 rounded bg-amber-100 text-amber-700">Archived</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded bg-emerald-100 text-emerald-700">Active</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 min-w-[220px]">
                                <div class="flex flex-wrap gap-1 mb-1">
                                    <span class="inline-flex px-2 py-0.5 rounded {{ $log->severity === 'critical' ? 'bg-red-100 text-red-700' : ($log->severity === 'high' ? 'bg-orange-100 text-orange-700' : ($log->severity === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700')) }}">{{ strtoupper($log->severity ?? 'info') }}</span>
                                    <span class="inline-flex px-2 py-0.5 rounded {{ $log->outcome === 'failure' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">{{ strtoupper($log->outcome ?? 'success') }}</span>
                                    @if($log->is_suspicious)
                                        <span class="inline-flex px-2 py-0.5 rounded bg-fuchsia-100 text-fuchsia-700">SUSPICIOUS</span>
                                    @endif
                                </div>
                                <div>Risk: <span class="font-semibold">{{ (int) ($log->risk_score ?? 0) }}/100</span></div>
                                <div>Category: {{ $log->event_category ?? '-' }}</div>
                                <div>Source: {{ $log->request_source ?? '-' }}</div>
                                <div class="break-all">Correlation: {{ $log->correlation_id ?? '-' }}</div>
                                @if(is_array($log->detection_tags) && $log->detection_tags !== [])
                                    <div class="mt-1">Tags: {{ implode(', ', $log->detection_tags) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                @if($log->checksum)
                                    <div class="font-mono break-all">{{ $log->checksum }}</div>
                                @else
                                    <span class="text-gray-400">legacy record</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 max-w-md">
                                @if($log->old_values)
                                    <div class="mb-2">
                                        <span class="font-semibold text-gray-700">Before:</span>
                                        <pre class="whitespace-pre-wrap bg-gray-50 p-2 rounded mt-1">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                @endif
                                @if($log->new_values)
                                    <div>
                                        <span class="font-semibold text-gray-700">After:</span>
                                        <pre class="whitespace-pre-wrap bg-gray-50 p-2 rounded mt-1">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                @endif
                                @if(! $log->old_values && ! $log->new_values)
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">No audit entries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t bg-gray-50">
            {{ $auditLogs->links() }}
        </div>
    </div>
@endsection
