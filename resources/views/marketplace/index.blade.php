<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('Flea market') }} — {{ $appName }}</title>

        @include('layouts._theme-head')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-100 min-h-screen dark:bg-gray-700 dark:text-gray-100">
        <header class="bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <x-application-logo class="w-9 h-9 text-gray-700 shrink-0 dark:text-gray-300" />
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $appName }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Flea market') }}</div>
                    </div>
                </div>
                @include('layouts._theme-toggle')
            </div>
        </header>

        <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="lg:flex lg:items-start lg:gap-8">

                @if ($categories->isNotEmpty())
                    <aside class="mb-6 lg:mb-0 lg:w-52 shrink-0">
                        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 dark:text-gray-400">{{ __('Categories') }}</h2>
                        <nav class="bg-white rounded-lg shadow divide-y divide-gray-100 overflow-hidden text-sm dark:bg-gray-800 dark:divide-gray-700">
                            <a href="{{ request()->fullUrlWithQuery(['category_id' => null, 'page' => null]) }}"
                               @class(['flex justify-between px-4 py-2', 'bg-gray-900 text-white' => ! $categoryId, 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' => $categoryId])>
                                <span>{{ __('All') }}</span>
                                <span @class(['text-gray-400 dark:text-gray-500' => $categoryId])>{{ $totalCount }}</span>
                            </a>
                            @foreach ($categories as $category)
                                <a href="{{ request()->fullUrlWithQuery(['category_id' => $category->id, 'page' => null]) }}"
                                   @class(['flex justify-between px-4 py-2', 'bg-gray-900 text-white' => $categoryId === $category->id, 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' => $categoryId !== $category->id])>
                                    <span>{{ $category->name }}</span>
                                    <span @class(['text-gray-400 dark:text-gray-500' => $categoryId !== $category->id])>{{ $category->listings_count }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </aside>
                @endif

                <div class="flex-1 min-w-0 space-y-6">

                    @if ($contactEmail || $contactPhone)
                        <div class="bg-indigo-50 border border-indigo-100 text-indigo-900 rounded-lg p-4 flex flex-wrap items-center justify-between gap-3 dark:bg-indigo-950 dark:text-indigo-300 dark:border-indigo-700">
                            <p class="text-sm">{{ __('Interested in one of the items below? Get in touch.') }}</p>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm font-medium">
                                @if ($contactEmail)
                                    <a href="mailto:{{ $contactEmail }}" class="text-indigo-700 hover:underline dark:text-indigo-400">✉️ {{ $contactEmail }}</a>
                                @endif
                                @if ($contactPhone)
                                    <a href="tel:{{ $contactPhone }}" class="text-indigo-700 hover:underline dark:text-indigo-400">📞 {{ $contactPhone }}</a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-between gap-4 flex-wrap">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('Items for sale') }}</h1>

                        <div class="flex rounded-md border border-gray-300 overflow-hidden shrink-0 bg-white dark:bg-gray-800 dark:border-gray-600" role="group" aria-label="{{ __('List view') }}">
                            <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}"
                               title="{{ __('Grid view') }}"
                               @class(['px-3 py-2 text-sm', 'bg-gray-900 text-white' => $view === 'grid', 'text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700' => $view !== 'grid'])>
                                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path d="M3 3h6v6H3V3zm8 0h6v6h-6V3zM3 11h6v6H3v-6zm8 0h6v6h-6v-6z"/></svg>
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}"
                               title="{{ __('List view') }}"
                               @class(['px-3 py-2 text-sm border-l border-gray-300 dark:border-gray-600', 'bg-gray-900 text-white' => $view === 'list', 'text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700' => $view !== 'list'])>
                                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 6a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 6a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                            </a>
                        </div>
                    </div>

                    @if ($listings->isEmpty())
                        <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                            {{ __('Nothing for sale right now — check back later.') }}
                        </div>
                    @elseif ($view === 'list')
                        <div class="bg-white rounded-lg shadow divide-y divide-gray-100 dark:bg-gray-800 dark:divide-gray-700">
                            @foreach ($listings as $listing)
                                @php $photos = $listing->item?->photosForGallery() ?? collect(); @endphp
                                <div class="p-4 flex gap-4 items-start">
                                    <div class="w-20 h-20 rounded-md bg-gray-100 overflow-hidden shrink-0 relative dark:bg-gray-700"
                                         @if ($photos->count() > 1) x-data="{ active: 0 }" @endif>
                                        @if ($photos->count() > 1)
                                            @foreach ($photos as $i => $photo)
                                                <img x-show="active === {{ $i }}" src="{{ $photo->url() }}" alt=""
                                                     class="w-full h-full object-cover cursor-pointer"
                                                     @click="active = (active + 1) % {{ $photos->count() }}">
                                            @endforeach
                                            <span class="absolute bottom-0.5 right-0.5 bg-black/60 text-white text-[10px] leading-none px-1 py-0.5 rounded pointer-events-none" x-text="(active + 1) + '/{{ $photos->count() }}'"></span>
                                        @elseif ($photos->isNotEmpty())
                                            <img src="{{ $photos->first()->url() }}" alt="" class="w-full h-full object-cover">
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-baseline justify-between gap-3 flex-wrap">
                                            <h2 class="font-medium text-gray-900 dark:text-gray-100">{{ $listing->title }}</h2>
                                            <span class="font-semibold text-gray-900 whitespace-nowrap dark:text-gray-100">{{ number_format((float) $listing->price, 2, ',', ' ') }} zł</span>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1 whitespace-pre-line dark:text-gray-400">{{ $listing->description }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($listings as $listing)
                                @php $photos = $listing->item?->photosForGallery() ?? collect(); @endphp
                                <div class="bg-white rounded-lg shadow overflow-hidden flex flex-col dark:bg-gray-800"
                                     @if ($photos->count() > 1) x-data="{ active: 0 }" @endif>
                                    <div class="aspect-video bg-gray-100 dark:bg-gray-700">
                                        @if ($photos->count() > 1)
                                            @foreach ($photos as $i => $photo)
                                                <img x-show="active === {{ $i }}" src="{{ $photo->url() }}" alt="" class="w-full h-full object-cover">
                                            @endforeach
                                        @elseif ($photos->isNotEmpty())
                                            <img src="{{ $photos->first()->url() }}" alt="" class="w-full h-full object-cover">
                                        @endif
                                    </div>
                                    @if ($photos->count() > 1)
                                        <div class="flex gap-1 p-2 bg-gray-50 overflow-x-auto dark:bg-gray-900">
                                            @foreach ($photos as $i => $photo)
                                                <button type="button" @click="active = {{ $i }}"
                                                        class="w-10 h-10 rounded overflow-hidden shrink-0 border-2"
                                                        :class="active === {{ $i }} ? 'border-indigo-500' : 'border-transparent'">
                                                    <img src="{{ $photo->url() }}" alt="" class="w-full h-full object-cover">
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="p-4 flex-1 flex flex-col">
                                        <div class="flex items-baseline justify-between gap-3">
                                            <h2 class="font-medium text-gray-900 dark:text-gray-100">{{ $listing->title }}</h2>
                                        </div>
                                        <span class="font-semibold text-gray-900 mt-1 dark:text-gray-100">{{ number_format((float) $listing->price, 2, ',', ' ') }} zł</span>
                                        <p class="text-sm text-gray-600 mt-2 flex-1 whitespace-pre-line dark:text-gray-400">{{ $listing->description }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div>
                        {{ $listings->links() }}
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
