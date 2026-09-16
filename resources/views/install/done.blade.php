<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('Installation complete') }} — Craty</title>

        @include('layouts._theme-head')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-100 min-h-screen flex items-center dark:bg-gray-700 dark:text-gray-100 relative">
        <div class="absolute top-4 right-4">
            @include('layouts._theme-toggle')
        </div>

        <div class="max-w-lg mx-auto px-4 w-full">
            <div class="flex flex-col items-center gap-2 mb-6">
                <x-application-logo class="w-14 h-14 text-emerald-600 dark:text-emerald-400" />
                <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-200">{{ __('Installation complete') }}</h1>
            </div>

            <div class="bg-white rounded-lg shadow p-6 space-y-4 dark:bg-gray-800">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    {{ __('Craty is ready. Log in with the administrator account you just created (:email).', ['email' => $adminEmail]) }}
                </p>

                <div class="bg-amber-50 border border-amber-200 text-amber-900 text-sm rounded-md p-4 dark:bg-amber-950 dark:border-amber-800 dark:text-amber-300">
                    <p class="font-medium mb-1">{{ __('One more thing (recommended)') }}</p>
                    <p>{{ __('The installer is already locked automatically — visiting it again now just redirects here. For extra peace of mind, you can also delete these from the server:') }}</p>
                    <ul class="list-disc list-inside mt-2 font-mono text-xs space-y-0.5">
                        <li>routes/install.php</li>
                        <li>app/Http/Controllers/InstallController.php</li>
                        <li>resources/views/install/</li>
                    </ul>
                    <form method="POST" action="{{ route('install.cleanup') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-amber-800 text-white text-xs font-medium rounded-md hover:bg-amber-900">
                            {{ __('Delete installer files now') }}
                        </button>
                    </form>
                </div>

                <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    {{ __('Go to login') }}
                </a>
            </div>
        </div>
    </body>
</html>
