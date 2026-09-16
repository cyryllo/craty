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
    <body class="font-sans text-gray-900 antialiased dark:text-gray-100">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-700 relative">
            <div class="absolute top-4 right-4">
                @include('layouts._theme-toggle')
            </div>

            <div class="flex flex-col items-center gap-2">
                <a href="/">
                    <x-application-logo class="w-16 h-16 fill-current text-gray-700 dark:text-gray-300" />
                </a>
                <span class="font-semibold text-gray-700 tracking-tight dark:text-gray-300">{{ \App\Models\AppSetting::current()->effectiveName() }}</span>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg dark:bg-gray-800">
                {{ $slot }}
            </div>

            @include('layouts._footer')
        </div>
    </body>
</html>
