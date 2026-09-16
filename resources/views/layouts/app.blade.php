<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ \App\Models\AppSetting::current()->effectiveName() }}</title>

        @include('layouts._theme-head')
        @include('layouts._pwa-head')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 dark:text-gray-100">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-700">
            @include('layouts.navigation')

            @if (session('status'))
                <div class="max-w-7xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
                    <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-300 dark:bg-emerald-950 dark:border-emerald-800">
                        {{ session('status') }}
                    </div>
                </div>
            @endif
            @if (session('error'))
                <div class="max-w-7xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
                    <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800 dark:bg-red-950 dark:border-red-800 dark:text-red-300">
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow dark:bg-gray-800">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
