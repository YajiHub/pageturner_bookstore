@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <svg class="mx-auto h-12 w-12 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16H5a2 2 0 01-2-2V6a2 2 0 012-2h12a2 2 0 012 2v8m-7-4l3 3m0 0l-3 3m3-3H9" />
            </svg>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Connection Error
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                {{ $message ?? 'We could not connect to the server. Please check your internet connection.' }}
            </p>
        </div>

        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        <strong>What you can try:</strong>
                        <ul class="list-disc list-inside mt-2 space-y-1">
                            <li>Check your internet connection</li>
                            <li>Refresh the page</li>
                            <li>Try again in a few moments</li>
                            <li>Clear your browser cache</li>
                        </ul>
                    </p>
                </div>
            </div>
        </div>

        <div class="flex gap-4">
            <button onclick="location.reload()" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                Retry
            </button>
            <a href="{{ route('home') }}" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition text-center">
                Go Home
            </a>
        </div>
    </div>
</div>
@endsection
