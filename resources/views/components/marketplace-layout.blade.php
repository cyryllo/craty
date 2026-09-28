{{-- Wspólna rama publicznego pchlego targu (lista ofert + strona pojedynczej oferty). --}}
@props(['appName', 'title' => null])
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ? $title.' — ' : '' }}{{ __('Flea market') }} — {{ $appName }}</title>

        @include('layouts._theme-head')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-100 min-h-screen dark:bg-gray-700 dark:text-gray-100">
        <header class="bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between gap-3">
                <a href="{{ route('marketplace.index') }}" class="flex items-center gap-3">
                    <x-application-logo class="w-9 h-9 text-gray-700 shrink-0 dark:text-gray-300" />
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $appName }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Flea market') }}</div>
                    </div>
                </a>
                @include('layouts._theme-toggle')
            </div>
        </header>

        <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            {{ $slot }}
        </main>

        @include('layouts._footer')
    </body>
</html>
