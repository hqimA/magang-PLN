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

        <!-- Hugeicons -->
        <link rel="stylesheet" href="https://use.hugeicons.com/font/icons.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="app-shell font-sans antialiased text-slate-900">
        <div class="min-h-screen">
            @include('layouts.sidebar')

            <div class="lg:ps-64">
                <!-- Mobile Top Bar -->
                <div class="app-header sticky top-0 z-20 flex h-16 items-center gap-3 border-b px-4 backdrop-blur-md lg:hidden">
                    <button type="button" @click="$store.sidebar.toggle()" class="inline-flex items-center justify-center rounded-xl p-2 text-sky-700 transition duration-150 ease-in-out hover:bg-sky-50 hover:text-sky-900 focus:outline-none focus:ring-2 focus:ring-sky-200">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path x-show="! $store.sidebar.open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="$store.sidebar.open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-semibold tracking-tight text-sky-950">
                        <x-application-logo class="block h-8 w-8 shrink-0 rounded-md shadow-sm shadow-yellow-500/20" />
                        <span>PLN Fleet</span>
                    </a>
                </div>

                <!-- Page Heading -->
                @isset($header)
                    <header class="app-header border-b backdrop-blur-xl">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="app-main">
                    {{-- Global Flash Messages --}}
                    @if (session('success'))
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-4">
                            <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-green-800 shadow-sm">
                                {{ session('success') }}
                            </div>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-4">
                            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 shadow-sm">
                                {{ session('error') }}
                            </div>
                        </div>
                    @endif
                    {{ $slot }}
                </main>
            </div>
        </div>

        {{-- Global Image Fullscreen Lightbox --}}
        <x-image-modal />
    </body>
</html>
