@extends('layouts.app')

@section('title', 'System Backups')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">System Backups</h2>
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Available Database & Storage Backups</h3>
            <p class="text-sm text-gray-500">These backups are generated automatically by Spatie Laravel-Backup.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">File Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Size</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($backups as $backup)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $backup['name'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $backup['size'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $backup['date'] }}</td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <a href="{{ route('admin.backups.download', $backup['name']) }}" class="text-indigo-600 hover:text-indigo-900 inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    Download
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">No backups found on the configured disk.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection