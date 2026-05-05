@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin Dashboard</h2>
@endsection

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Customers</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total_users'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Books</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total_books'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-full">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Categories</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total_categories'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-orange-100 rounded-full">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Orders</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total_orders'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow mb-6 border border-slate-200 p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="rounded-lg px-3 py-2 border {{ $transferHealth['stalled_processing'] > 0 ? 'bg-red-50 border-red-200' : 'bg-emerald-50 border-emerald-200' }}">
                <div class="text-xs uppercase tracking-wide {{ $transferHealth['stalled_processing'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">Stalled Transfers</div>
                <div class="text-xl font-semibold {{ $transferHealth['stalled_processing'] > 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ number_format($transferHealth['stalled_processing']) }}</div>
            </div>
            <div class="rounded-lg px-3 py-2 border {{ $transferHealth['failed'] > 0 ? 'bg-amber-50 border-amber-200' : 'bg-slate-50 border-slate-200' }}">
                <div class="text-xs uppercase tracking-wide {{ $transferHealth['failed'] > 0 ? 'text-amber-700' : 'text-slate-500' }}">Failed Transfers</div>
                <div class="text-xl font-semibold {{ $transferHealth['failed'] > 0 ? 'text-amber-700' : 'text-slate-700' }}">{{ number_format($transferHealth['failed']) }}</div>
            </div>
            <div class="rounded-lg px-3 py-2 border {{ $orderStatusSummary['pending'] > 0 ? 'bg-blue-50 border-blue-200' : 'bg-slate-50 border-slate-200' }}">
                <div class="text-xs uppercase tracking-wide {{ $orderStatusSummary['pending'] > 0 ? 'text-blue-700' : 'text-slate-500' }}">Pending Orders</div>
                <div class="text-xl font-semibold {{ $orderStatusSummary['pending'] > 0 ? 'text-blue-700' : 'text-slate-700' }}">{{ number_format($orderStatusSummary['pending']) }}</div>
            </div>
        </div>
    </div>

    <div class="sticky top-20 z-30 mb-8">
        <div class="bg-white/95 backdrop-blur border border-slate-200 rounded-lg shadow px-3 py-2 flex flex-wrap gap-2 text-xs sm:text-sm">
            <a href="#overview-insights" class="px-3 py-1.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700">Overview</a>
            <a href="#quick-actions" class="px-3 py-1.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700">Quick Actions</a>
            <a href="#system-observability" class="px-3 py-1.5 rounded-md bg-indigo-100 hover:bg-indigo-200 text-indigo-800 font-medium">Observability</a>
            <a href="#data-management" class="px-3 py-1.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700">Data Management</a>
            <a href="#transfer-jobs" class="px-3 py-1.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700">Transfer Jobs</a>
            <a href="#recent-audit" class="px-3 py-1.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700">Audit Logs</a>
        </div>
    </div>

    <div id="overview-insights" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 scroll-mt-28">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Sales Performance</h3>
            <div class="space-y-3">
                <div class="p-3 rounded-lg bg-emerald-50 border border-emerald-100">
                    <div class="text-sm text-emerald-700">Today</div>
                    <div class="text-lg font-semibold text-emerald-900">₱{{ number_format($salesMetrics['today']['revenue'], 2) }}</div>
                    <div class="text-xs text-emerald-700">{{ number_format($salesMetrics['today']['orders']) }} completed orders</div>
                </div>
                <div class="p-3 rounded-lg bg-blue-50 border border-blue-100">
                    <div class="text-sm text-blue-700">Last 7 Days</div>
                    <div class="text-lg font-semibold text-blue-900">₱{{ number_format($salesMetrics['last_7_days']['revenue'], 2) }}</div>
                    <div class="text-xs text-blue-700">{{ number_format($salesMetrics['last_7_days']['orders']) }} completed orders</div>
                </div>
                <div class="p-3 rounded-lg bg-violet-50 border border-violet-100">
                    <div class="text-sm text-violet-700">Last 30 Days</div>
                    <div class="text-lg font-semibold text-violet-900">₱{{ number_format($salesMetrics['last_30_days']['revenue'], 2) }}</div>
                    <div class="text-xs text-violet-700">{{ number_format($salesMetrics['last_30_days']['orders']) }} completed orders</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Transfer Queue Health</h3>
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                    <div class="text-xs text-gray-500">Queued</div>
                    <div class="text-lg font-semibold text-gray-800">{{ number_format($transferHealth['queued']) }}</div>
                </div>
                <div class="p-3 rounded-lg bg-blue-50 border border-blue-100">
                    <div class="text-xs text-blue-600">Processing</div>
                    <div class="text-lg font-semibold text-blue-800">{{ number_format($transferHealth['processing']) }}</div>
                </div>
                <div class="p-3 rounded-lg bg-green-50 border border-green-100">
                    <div class="text-xs text-green-600">Completed</div>
                    <div class="text-lg font-semibold text-green-800">{{ number_format($transferHealth['completed']) }}</div>
                </div>
                <div class="p-3 rounded-lg bg-red-50 border border-red-100">
                    <div class="text-xs text-red-600">Failed</div>
                    <div class="text-lg font-semibold text-red-800">{{ number_format($transferHealth['failed']) }}</div>
                </div>
            </div>
            <div class="text-sm {{ $transferHealth['stalled_processing'] > 0 ? 'text-red-700' : 'text-green-700' }}">
                Stalled processing jobs (&gt;90m): <span class="font-semibold">{{ number_format($transferHealth['stalled_processing']) }}</span>
            </div>
        </div>
    </div>

    <div id="system-observability" class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 scroll-mt-28">
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">System Health</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center border-b pb-2">
                    <span class="text-sm text-gray-600">Database Size</span>
                    <span class="font-semibold text-gray-800">{{ $systemHealth['database_size'] }}</span>
                </div>
                <div class="flex justify-between items-center border-b pb-2">
                    <span class="text-sm text-gray-600">Storage Usage</span>
                    <span class="font-semibold text-gray-800">{{ $systemHealth['storage_usage_percent'] }} ({{ $systemHealth['free_space_gb'] }} free)</span>
                </div>
                <div class="flex justify-between items-center border-b pb-2">
                    <span class="text-sm text-gray-600">Failed Queue Jobs</span>
                    <span class="font-semibold {{ $systemHealth['failed_jobs'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $systemHealth['failed_jobs'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Current Queue Length</span>
                    <span class="font-semibold text-blue-600">{{ $systemHealth['queue_length'] }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">API Usage Statistics</h3>
            <div class="flex justify-between items-center mb-3">
                <span class="text-sm text-gray-600">Total Requests:</span>
                <span class="font-semibold text-gray-800">{{ number_format($apiUsage['total_requests']) }}</span>
            </div>
            <div class="flex justify-between items-center mb-4">
                <span class="text-sm text-gray-600">Rate Limit Throttles:</span>
                <span class="font-semibold text-red-600">{{ number_format($apiUsage['rate_limit_hits']) }}</span>
            </div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Top Hit Endpoints</h4>
            <div class="space-y-2">
                @foreach($apiUsage['endpoints'] as $ep)
                <div class="flex justify-between items-center text-sm bg-gray-50 p-2 rounded">
                    <span class="text-indigo-600 font-mono text-xs">{{ is_array($ep) ? $ep['endpoint'] : $ep->endpoint }}</span>
                    <span class="text-gray-700">{{ number_format(is_array($ep) ? $ep['hits'] : $ep->hits) }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Backup Status</h3>
            <div class="flex flex-col items-center justify-center mb-4">
                <div class="w-16 h-16 rounded-full {{ $backupStatus['health'] === 'Healthy' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }} flex items-center justify-center mb-2">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <span class="font-semibold text-lg {{ $backupStatus['health'] === 'Healthy' ? 'text-green-700' : 'text-red-700' }}">{{ $backupStatus['health'] }}</span>
            </div>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-600">Last Verified Backup:</span>
                    <span class="font-medium text-gray-800">{{ \Carbon\Carbon::parse($backupStatus['last_backup_time'])->diffForHumans() }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-600">Avg. Archive Size:</span>
                    <span class="font-medium text-gray-800">{{ $backupStatus['size'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Storage Destination:</span>
                    <span class="font-medium text-gray-800 uppercase">{{ $backupStatus['location'] }}</span>
                </div>
            </div>
        </div>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Low Stock Alert (≤ {{ $lowStockThreshold }})</h3>
            <div class="space-y-2">
                @forelse($lowStockBooks as $book)
                    <div class="flex items-center justify-between p-2 rounded bg-amber-50 border border-amber-100">
                        <a href="{{ route('books.show', $book->id) }}" class="text-sm text-gray-800 hover:text-indigo-600">{{ $book->title }}</a>
                        <span class="text-xs font-semibold px-2 py-1 rounded {{ $book->stock_quantity <= 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $book->stock_quantity }} left
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No low-stock books right now.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Selling Books Analytics</h3>
            <canvas id="topSellingChart" height="180"></canvas>
            <div class="space-y-2 mt-4 hidden">
                @forelse($topSellingBooks as $book)
                    <div class="p-2 rounded bg-gray-50 border border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-800">{{ $book->title }}</span>
                            <span class="text-xs text-gray-600">{{ number_format($book->total_units) }} units</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Revenue: ₱{{ number_format((float) $book->total_revenue, 2) }}</div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No completed-order sales data yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div id="quick-actions" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 scroll-mt-28">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Order Status Summary</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="flex items-center">
                        <span class="w-3 h-3 bg-yellow-400 rounded-full mr-2"></span>
                        Pending
                    </span>
                    <span class="font-semibold text-yellow-600">{{ $orderStatusSummary['pending'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="flex items-center">
                        <span class="w-3 h-3 bg-blue-400 rounded-full mr-2"></span>
                        Processing
                    </span>
                    <span class="font-semibold text-blue-600">{{ $orderStatusSummary['processing'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="flex items-center">
                        <span class="w-3 h-3 bg-green-400 rounded-full mr-2"></span>
                        Completed
                    </span>
                    <span class="font-semibold text-green-600">{{ $orderStatusSummary['completed'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="flex items-center">
                        <span class="w-3 h-3 bg-red-400 rounded-full mr-2"></span>
                        Cancelled
                    </span>
                    <span class="font-semibold text-red-600">{{ $orderStatusSummary['cancelled'] }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Links</h3>
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('admin.books.create') }}" class="flex items-center p-3 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                    <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span class="text-sm font-medium text-indigo-700">Add Book</span>
                </a>
                <a href="{{ route('admin.categories.create') }}" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                    <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span class="text-sm font-medium text-purple-700">Add Category</span>
                </a>
                <a href="{{ route('books.index') }}" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition">
                    <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <span class="text-sm font-medium text-green-700">Manage Books</span>
                </a>
                <a href="{{ route('categories.index') }}" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                    <span class="text-sm font-medium text-blue-700">Manage Categories</span>
                </a>
                <a href="{{ route('orders.index') }}" class="flex items-center p-3 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                    <svg class="w-5 h-5 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <span class="text-sm font-medium text-orange-700">Manage Orders</span>
                </a>
                <a href="{{ route('profile.edit') }}" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-700">Settings</span>
                </a>
                <a href="{{ route('admin.audit-logs.index') }}" class="flex items-center p-3 bg-amber-50 rounded-lg hover:bg-amber-100 transition">
                    <svg class="w-5 h-5 text-amber-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="text-sm font-medium text-amber-700">Audit Logs</span>
                </a>
                <a href="{{ route('admin.backups.index') }}" class="flex items-center p-3 bg-teal-50 rounded-lg hover:bg-teal-100 transition">
                    <svg class="w-5 h-5 text-teal-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                    </svg>
                    <span class="text-sm font-medium text-teal-700">System Backups</span>
                </a>
            </div>
        </div>
    </div>

    <div id="data-management" class="bg-white rounded-lg shadow mb-8 scroll-mt-28">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-800">System Data Management</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                    <div class="flex items-center mb-2">
                        <svg class="w-6 h-6 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <h4 class="font-medium text-gray-800">Export Books</h4>
                    </div>
                    <p class="text-sm text-gray-500 mb-4">Export with format, filters, and custom columns. Exports above 10,000 rows are queued automatically.</p>
                    <form action="{{ route('admin.books.export') }}" method="POST" class="space-y-2">
                        @csrf
                        <div class="grid grid-cols-2 gap-2">
                            <select name="format" class="rounded-md border-gray-300 text-sm">
                                <option value="xlsx">XLSX</option>
                                <option value="csv">CSV</option>
                                <option value="pdf">PDF</option>
                            </select>
                            <select name="category_id" class="rounded-md border-gray-300 text-sm">
                                <option value="">All categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-1 gap-2">
                            <select name="stock_status" class="rounded-md border-gray-300 text-sm">
                                <option value="">All stock states</option>
                                <option value="in_stock">In stock</option>
                                <option value="out_of_stock">Out of stock</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" step="0.01" min="0" name="min_price" placeholder="Min price" class="rounded-md border-gray-300 text-sm">
                            <input type="number" step="0.01" min="0" name="max_price" placeholder="Max price" class="rounded-md border-gray-300 text-sm">
                        </div>
                        <input type="text" name="search" placeholder="Search title, author, ISBN" class="w-full rounded-md border-gray-300 text-sm">
                        <label class="block text-xs font-medium text-gray-600">Columns</label>
                        <div class="grid grid-cols-2 gap-1 text-xs text-gray-700">
                            @foreach(\App\Exports\BooksExport::availableColumns() as $key => $label)
                                <label class="inline-flex items-center gap-1">
                                    <input type="checkbox" name="columns[]" value="{{ $key }}" checked>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="inline-flex w-full justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none transition">
                            Start Export
                        </button>
                    </form>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                    <div class="flex items-center mb-2">
                        <svg class="w-6 h-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <h4 class="font-medium text-gray-800">Import Books</h4>
                    </div>
                    <p class="text-sm text-gray-500 mb-2">Use strict template headers, preview, and then import with duplicate handling mode.</p>
                    <a href="{{ route('admin.books.import.template') }}" class="inline-flex mb-3 text-xs text-green-700 hover:text-green-900">Download import template (CSV)</a>
                    <form action="{{ route('admin.books.import.preview') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="flex flex-col gap-2">
                            <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                            <button type="submit" class="inline-flex w-full justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none transition">
                                Upload & Preview
                            </button>
                        </div>
                        @error('file')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </form>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                    <div class="flex items-center mb-2">
                        <svg class="w-6 h-6 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        <h4 class="font-medium text-gray-800">Export Orders</h4>
                    </div>
                    <p class="text-sm text-gray-500 mb-4">Export order data with date/status filters and custom fields. Large exports are queued automatically.</p>
                    <form action="{{ route('admin.orders.export') }}" method="POST" class="space-y-2">
                        @csrf
                        <div class="grid grid-cols-2 gap-2">
                            <select name="format" class="rounded-md border-gray-300 text-sm">
                                <option value="xlsx">XLSX</option>
                                <option value="csv">CSV</option>
                                <option value="pdf">PDF</option>
                            </select>
                            <select name="status" class="rounded-md border-gray-300 text-sm">
                                <option value="">All statuses</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" name="from_date" class="rounded-md border-gray-300 text-sm">
                            <input type="date" name="to_date" class="rounded-md border-gray-300 text-sm">
                        </div>
                        <input type="text" name="search" placeholder="Search order id, customer, email" class="w-full rounded-md border-gray-300 text-sm">
                        <label class="block text-xs font-medium text-gray-600">Columns</label>
                        <div class="grid grid-cols-2 gap-1 text-xs text-gray-700">
                            @foreach(\App\Exports\OrdersExport::availableColumns() as $key => $label)
                                <label class="inline-flex items-center gap-1">
                                    <input type="checkbox" name="columns[]" value="{{ $key }}" checked>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="inline-flex w-full justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none transition">
                            Start Orders Export
                        </button>
                    </form>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                    <div class="flex items-center mb-2">
                        <svg class="w-6 h-6 text-sky-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5V4H2v16h5m10 0v-2a4 4 0 00-4-4H11a4 4 0 00-4 4v2m10 0H7m10 0h-2m-6 0H7m4-8a3 3 0 110-6 3 3 0 010 6z"></path></svg>
                        <h4 class="font-medium text-gray-800">Export Users</h4>
                    </div>
                    <p class="text-sm text-gray-500 mb-4">Export users with optional PII redaction for email and address fields.</p>
                    <form action="{{ route('admin.users.export') }}" method="POST" class="space-y-2">
                        @csrf
                        <div class="grid grid-cols-2 gap-2">
                            <select name="format" class="rounded-md border-gray-300 text-sm">
                                <option value="xlsx">XLSX</option>
                                <option value="csv">CSV</option>
                                <option value="pdf">PDF</option>
                            </select>
                            <select name="role" class="rounded-md border-gray-300 text-sm">
                                <option value="">All roles</option>
                                <option value="admin">Admin</option>
                                <option value="customer">Customer</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" name="from_date" class="rounded-md border-gray-300 text-sm">
                            <input type="date" name="to_date" class="rounded-md border-gray-300 text-sm">
                        </div>
                        <input type="text" name="search" placeholder="Search name or email" class="w-full rounded-md border-gray-300 text-sm">
                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-700">
                            <label class="inline-flex items-center gap-1">
                                <input type="checkbox" name="redact_email" value="1">
                                <span>Redact email</span>
                            </label>
                            <label class="inline-flex items-center gap-1">
                                <input type="checkbox" name="redact_address" value="1">
                                <span>Redact address</span>
                            </label>
                        </div>
                        <label class="block text-xs font-medium text-gray-600">Columns</label>
                        <div class="grid grid-cols-2 gap-1 text-xs text-gray-700">
                            @foreach(\App\Exports\UsersExport::availableColumns() as $key => $label)
                                <label class="inline-flex items-center gap-1">
                                    <input type="checkbox" name="columns[]" value="{{ $key }}" checked>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="inline-flex w-full justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-sky-600 hover:bg-sky-700 focus:outline-none transition">
                            Start Users Export
                        </button>
                    </form>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                    <div class="flex items-center mb-2">
                        <svg class="w-6 h-6 text-teal-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <h4 class="font-medium text-gray-800">Import Users</h4>
                    </div>
                    <p class="text-sm text-gray-500 mb-2">Use strict user template and duplicate mode (skip/update by email).</p>
                    <a href="{{ route('admin.users.import.template') }}" class="inline-flex mb-3 text-xs text-teal-700 hover:text-teal-900">Download user import template (CSV)</a>
                    <form action="{{ route('admin.users.import.preview') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="flex flex-col gap-2">
                            <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                            <button type="submit" class="inline-flex w-full justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-teal-600 hover:bg-teal-700 focus:outline-none transition">
                                Upload & Preview Users
                            </button>
                        </div>
                        @error('file')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="transfer-jobs" class="bg-white rounded-lg shadow mb-8 scroll-mt-28" x-data="transferJobsWidget({ initialJobs: @js($recentTransfersPayload), endpoint: '{{ route('admin.transfer-jobs.progress') }}' })" x-init="init()" x-on:beforeunload.window="destroy()">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Data Transfer Jobs</h3>
            <span class="text-xs text-gray-500">Auto-refreshes every few seconds</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">File</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status & Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Result</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-if="jobs.length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">No transfer jobs yet.</td>
                        </tr>
                    </template>
                    <template x-for="job in jobs" :key="job.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-700 uppercase" x-text="job.type"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="job.requested_by"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="job.file"></td>
                            <td class="px-6 py-4 min-w-[220px]">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full" :class="job.status_class" x-text="job.status_label"></span>
                                <div class="mt-2">
                                    <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full transition-all duration-500 ease-out" :class="job.progress_bar_class" :style="`width: ${job.progress}%`"></div>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500" x-text="`${job.progress}%`"></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm" :class="job.result_class">
                                <template x-if="job.download_url">
                                    <a :href="job.download_url" class="text-indigo-600 hover:text-indigo-800">Download file</a>
                                </template>
                                <template x-if="!job.download_url">
                                    <span x-text="job.result_text"></span>
                                </template>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="job.created_human"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <div id="recent-audit" class="bg-white rounded-lg shadow mb-8 scroll-mt-28">
        <div class="p-6 border-b flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Recent Audit Logs</h3>
            <a href="{{ route('admin.audit-logs.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">View Full Log →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentAuditLogs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $log->created_at?->diffForHumans() }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $log->user?->name ?? 'System' }}</td>
                            <td class="px-6 py-4 text-sm text-indigo-700 font-medium">{{ $log->action }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $log->description ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">No audit logs yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="recent-orders" class="bg-white rounded-lg shadow mb-8 scroll-mt-28">
        <div class="p-6 border-b">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Recent Orders</h3>
                <a href="{{ route('orders.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">View All →</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentOrders as $order)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">#{{ $order->id }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $order->user?->name ?? 'Deleted User' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">₱{{ number_format($order->total_amount, 2) }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'processing' => 'bg-blue-100 text-blue-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $order->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('orders.show', $order) }}" class="text-indigo-600 hover:text-indigo-800 text-sm">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">No orders yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="recent-reviews" class="bg-white rounded-lg shadow scroll-mt-28">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Recent Reviews</h3>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($recentReviews as $review)
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-medium text-gray-800">{{ $review->user?->name ?? 'Deleted User' }}</p>
                            <p class="text-sm text-gray-500">reviewed <a href="{{ route('books.show', $review->book) }}" class="text-indigo-600 hover:underline">{{ $review->book->title }}</a></p>
                        </div>
                        <div class="flex items-center">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            @endfor
                        </div>
                    </div>
                    @if($review->comment)
                        <p class="mt-2 text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($review->comment, 150) }}</p>
                    @endif
                    <p class="mt-1 text-xs text-gray-400">{{ $review->created_at->diffForHumans() }}</p>
                </div>
            @empty
                <div class="p-6 text-center text-gray-500">No reviews yet.</div>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('topSellingChart').getContext('2d');
        const chartData = {!! json_encode($topSellingBooks) !!};
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.map(book => book.title),
                datasets: [{
                    label: 'Units Sold',
                    data: chartData.map(book => book.total_units),
                    backgroundColor: 'rgba(79, 70, 229, 0.5)',
                    borderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    });
</script>
@endpush