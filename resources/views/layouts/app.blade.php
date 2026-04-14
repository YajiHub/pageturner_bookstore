<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'PageTurner Bookstore')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen">
        @include('partials.navigation')

        <!-- Page Heading -->
        @hasSection('header')
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    @yield('header')
                </div>
            </header>
        @endif

        <!-- Flash Messages -->
        @include('partials.flash-messages')

        <!-- Page Content -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>

        @include('partials.footer')
    </div>

    @stack('scripts')
    {{-- floating cart button (hidden for admins) --}}
    @unless(auth()->check() && auth()->user()->isAdmin())
        <a href="{{ route('cart.index') }}" style="position:fixed;bottom:16px;right:16px;z-index:9999;width:56px;height:56px;background:#4f46e5;color:#fff;border-radius:9999px;display:flex;align-items:center;justify-content:center;box-shadow:0 10px 20px rgba(0,0,0,0.12);" aria-label="View cart">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:24px;height:24px;color:#fff;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.35 2.7A1 1 0 007 17h10a1 1 0 00.95-.68l1.54-4.32M7 13V6h13v7"></path>
            </svg>
        </a>
    @endunless
</body>
</html>
