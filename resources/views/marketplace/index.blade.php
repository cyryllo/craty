<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('Flea market') }} — {{ $appName }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-100 min-h-screen">
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center gap-3">
                <x-application-logo class="w-9 h-9 text-gray-700 shrink-0" />
                <div>
                    <div class="font-semibold text-gray-900">{{ $appName }}</div>
                    <div class="text-xs text-gray-500">{{ __('Flea market') }}</div>
                </div>
            </div>
        </header>

        <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <div class="flex items-center justify-between gap-4 flex-wrap">
                <h1 class="text-xl font-semibold text-gray-900">{{ __('Items for sale') }}</h1>

                <div class="flex rounded-md border border-gray-300 overflow-hidden shrink-0 bg-white" role="group" aria-label="{{ __('List view') }}">
                    <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}"
                       title="{{ __('Grid view') }}"
                       @class(['px-3 py-2 text-sm', 'bg-gray-900 text-white' => $view === 'grid', 'text-gray-500 hover:bg-gray-50' => $view !== 'grid'])>
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path d="M3 3h6v6H3V3zm8 0h6v6h-6V3zM3 11h6v6H3v-6zm8 0h6v6h-6v-6z"/></svg>
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}"
                       title="{{ __('List view') }}"
                       @class(['px-3 py-2 text-sm border-l border-gray-300', 'bg-gray-900 text-white' => $view === 'list', 'text-gray-500 hover:bg-gray-50' => $view !== 'list'])>
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 6a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 6a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                    </a>
                </div>
            </div>

            @if ($listings->isEmpty())
                <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                    {{ __('Nothing for sale right now — check back later.') }}
                </div>
            @elseif ($view === 'list')
                <div class="bg-white rounded-lg shadow divide-y divide-gray-100">
                    @foreach ($listings as $listing)
                        <div class="p-4 flex gap-4 items-start">
                            <div class="w-20 h-20 rounded-md bg-gray-100 overflow-hidden shrink-0">
                                @if ($listing->item?->primaryPhoto->first())
                                    <img src="{{ $listing->item->primaryPhoto->first()->url() }}" alt="" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-baseline justify-between gap-3 flex-wrap">
                                    <h2 class="font-medium text-gray-900">{{ $listing->title }}</h2>
                                    <span class="font-semibold text-gray-900 whitespace-nowrap">{{ number_format((float) $listing->price, 2, ',', ' ') }} zł</span>
                                </div>
                                <p class="text-sm text-gray-600 mt-1 whitespace-pre-line">{{ $listing->description }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                    @if ($contactEmail)
                                        <a href="mailto:{{ $contactEmail }}?subject={{ rawurlencode($listing->title) }}" class="text-indigo-600 hover:underline">
                                            {{ __('Contact us about this item') }}
                                        </a>
                                    @endif
                                    @if ($contactPhone)
                                        <a href="tel:{{ $contactPhone }}" class="text-gray-600 hover:underline">
                                            📞 {{ $contactPhone }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($listings as $listing)
                        <div class="bg-white rounded-lg shadow overflow-hidden flex flex-col">
                            <div class="aspect-video bg-gray-100">
                                @if ($listing->item?->primaryPhoto->first())
                                    <img src="{{ $listing->item->primaryPhoto->first()->url() }}" alt="" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="p-4 flex-1 flex flex-col">
                                <div class="flex items-baseline justify-between gap-3">
                                    <h2 class="font-medium text-gray-900">{{ $listing->title }}</h2>
                                </div>
                                <span class="font-semibold text-gray-900 mt-1">{{ number_format((float) $listing->price, 2, ',', ' ') }} zł</span>
                                <p class="text-sm text-gray-600 mt-2 flex-1 whitespace-pre-line">{{ $listing->description }}</p>
                                <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                    @if ($contactEmail)
                                        <a href="mailto:{{ $contactEmail }}?subject={{ rawurlencode($listing->title) }}" class="text-indigo-600 hover:underline">
                                            {{ __('Contact us about this item') }}
                                        </a>
                                    @endif
                                    @if ($contactPhone)
                                        <a href="tel:{{ $contactPhone }}" class="text-gray-600 hover:underline">
                                            📞 {{ $contactPhone }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div>
                {{ $listings->links() }}
            </div>
        </main>
    </body>
</html>
