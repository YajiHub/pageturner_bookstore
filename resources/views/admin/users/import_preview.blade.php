@extends('layouts.app')

@section('title', 'Confirm Users Import')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Review Users Import</h2>
        <a href="{{ route('admin.dashboard') }}" class="text-gray-500 hover:text-gray-700">Cancel & Return</a>
    </div>
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="border-b pb-4 mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-800">Uploaded File: <span class="text-indigo-600 font-mono text-sm bg-indigo-50 px-2 py-1 rounded">{{ $originalName }}</span></h3>
                <p class="text-sm text-gray-500 mt-1">Total Users detected: <strong>{{ number_format($recordCount) }} records</strong>.</p>
            </div>

            <form action="{{ route('admin.users.import.process') }}" method="POST">
                @csrf
                <input type="hidden" name="filepath" value="{{ $path }}">
                <input type="hidden" name="original_name" value="{{ $originalName }}">
                <input type="hidden" name="record_count" value="{{ $recordCount }}">
                <div class="mb-3">
                    <label for="duplicate_strategy" class="block text-xs font-medium text-gray-600 mb-1">Duplicate email handling</label>
                    <select id="duplicate_strategy" name="duplicate_strategy" class="w-full rounded-md border-gray-300 text-sm" required>
                        <option value="skip">Skip existing email rows</option>
                        <option value="update">Update existing email rows</option>
                    </select>
                </div>

                @if($recordCount > 0)
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none transition">
                        Confirm & Import Users
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
            <h4 class="font-medium text-gray-700 mb-3">Data Preview (top 5 rows)</h4>

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
                                        {{ \Illuminate\Support\Str::limit((string) $cell, 40) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
            <div class="text-center py-6 text-gray-500">
                The file appears to have no data rows.
            </div>
        @endif
    </div>
@endsection
