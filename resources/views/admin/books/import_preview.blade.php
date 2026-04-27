@extends('layouts.app')

@section('title', 'Confirm Books Import')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Review Books Import</h2>
        <a href="{{ route('admin.dashboard') }}" class="text-gray-500 hover:text-gray-700">Cancel & Return</a>
    </div>
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="border-b pb-4 mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-800">Uploaded File: <span class="text-indigo-600 font-mono text-sm bg-indigo-50 px-2 py-1 rounded">{{ $originalName }}</span></h3>
                <p class="text-sm text-gray-500 mt-1">Total Books detected: <strong>{{ number_format($recordCount) }} records</strong>.</p>
            </div>
            
            <form action="{{ route('admin.books.import.process') }}" method="POST">
                @csrf
                <input type="hidden" name="filepath" value="{{ $path }}">
                <input type="hidden" name="original_name" value="{{ $originalName }}">
                <input type="hidden" name="record_count" value="{{ $recordCount }}">
                <div class="mb-3">
                    <label for="duplicate_strategy" class="block text-xs font-medium text-gray-600 mb-1">Duplicate ISBN handling</label>
                    <select id="duplicate_strategy" name="duplicate_strategy" class="w-full rounded-md border-gray-300 text-sm" required>
                        <option value="skip">Skip existing ISBN rows</option>
                        <option value="update">Update existing ISBN rows</option>
                    </select>
                </div>
                
                @if($recordCount > 0)
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Confirm & Import Data
                    </button>
                @else
                   <button type="button" disabled class="inline-flex items-center px-4 py-2 shadow-sm text-sm font-medium rounded-md text-white bg-gray-400 cursor-not-allowed">
                        Empty File Detected
                    </button> 
                @endif
            </form>
        </div>

        @if($recordCount > 0)
        <div>
            <h4 class="font-medium text-gray-700 mb-3">Data Preview (showing top 5 rows max)</h4>
            
            <div class="overflow-x-auto border rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach($headers as $header)
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ $header }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($dataRows as $row)
                            <tr class="hover:bg-gray-50">
                                @foreach($row as $cell)
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ \Illuminate\Support\Str::limit((string) $cell, 30) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Note:</strong> All records will undergo validation against the system schemas. If duplicate ISBNs or invalid IDs are present, the whole import process will automatically gracefully reject specific rows and notify you. Make sure `category_id` values correspond to existing Database Categories.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @else
            <div class="text-center py-6 text-gray-500">
                The file you uploaded appears to have no data. Make sure it contains formatted headings and valid data underneath.
            </div>
        @endif
    </div>
@endsection