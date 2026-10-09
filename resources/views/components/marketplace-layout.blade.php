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
                <a href="{{ route('marketplace.index', [], false) }}" class="flex items-center gap-3">
                    <x-application-logo class="w-9 h-9 text-gray-700 shrink-0 dark:text-gray-300" />
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $appName }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Flea market') }}</div>
                    </div>
                </a>
                <div class="flex items-center gap-2">
                    @include('layouts._theme-toggle')
                    {{-- Strona główna jest publiczna — wejście do ewidencji: kłódka dla gości, "Panel" dla zalogowanych. --}}
                    @auth
                        <a href="{{ route('dashboard', [], false) }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium text-white bg-gray-900 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600">{{ __('Dashboard') }}</a>
                    @else
                        <a href="{{ route('login', [], false) }}" title="{{ __('Log in') }}" aria-label="{{ __('Log in') }}"
                           class="inline-flex items-center justify-center w-9 h-9 rounded-md text-gray-500 hover:text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700">
                            <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/></svg>
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            {{ $slot }}
        </main>

        {{-- Polityka prywatności / regulamin z Ustawień sklepu: linki nad stopką (tylko gdy tekst jest wpisany),
             treść w okienku, bez osobnych podstron. Markdown przerobiony bezpiecznie w AppSetting::markdown(). --}}
        @php
            $shop = \App\Models\AppSetting::current();
            $legal = array_filter([
                'privacy' => [__('Privacy policy'), \App\Models\AppSetting::markdown($shop->privacy_policy)],
                'terms' => [__('Terms and conditions'), \App\Models\AppSetting::markdown($shop->terms)],
            ], fn ($doc) => $doc[1] !== '');
        @endphp
        @if ($legal && \App\Support\Modules::isEnabled('sales'))
            <div x-data="{ doc: null }" @keydown.escape.window="doc = null" class="text-center text-xs pt-2">
                @foreach ($legal as $key => [$title, $html])
                    @if (! $loop->first) <span class="text-gray-400 dark:text-gray-500">&middot;</span> @endif
                    <button type="button" @click="doc = '{{ $key }}'" class="text-gray-500 hover:text-gray-800 hover:underline dark:text-gray-400 dark:hover:text-gray-200">{{ $title }}</button>
                @endforeach

                @foreach ($legal as $key => [$title, $html])
                    <div x-show="doc === '{{ $key }}'" x-cloak x-transition.opacity
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
                         role="dialog" aria-modal="true" aria-label="{{ $title }}" @click.self="doc = null">
                        <div class="w-full max-w-2xl max-h-[85dvh] flex flex-col rounded-lg bg-white text-left shadow-xl dark:bg-gray-800">
                            <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h2>
                                <button type="button" @click="doc = null" class="w-10 h-10 flex items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-700 dark:hover:text-white" aria-label="{{ __('Close') }}">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                                </button>
                            </div>
                            <div class="md-content overflow-y-auto px-6 py-5 text-sm text-gray-700 dark:text-gray-300">{!! $html !!}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @include('layouts._footer')
    </body>
</html>
