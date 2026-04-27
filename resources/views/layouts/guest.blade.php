<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased relative min-h-screen flex flex-col">
        <!-- Fixed Immersive Background -->
        <div class="fixed inset-0 z-[-1]">
            <img class="w-full h-full object-cover opacity-30" src="https://images.unsplash.com/photo-1481627834876-b7833e8f5570?q=80&w=2228&auto=format&fit=crop" alt="Library Background">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm"></div>
        </div>

        <div class="flex-grow flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-white" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }} 
            </div>
        </div>
    </body>
</html>
