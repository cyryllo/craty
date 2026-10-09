<x-marketplace-layout :app-name="$appName">
    @if (! $enabled)
        {{-- Pchli targ wyłączony (albo moduł Sprzedaż) — strona główna zostaje, tylko bez ofert. --}}
        <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500 dark:bg-gray-800 dark:text-gray-400">
            {{ __('Nothing for sale right now — check back later.') }}
        </div>
    @else
            <div class="lg:flex lg:items-start lg:gap-8">

                @if ($categories->isNotEmpty())
                    <aside class="mb-6 lg:mb-0 lg:w-52 shrink-0">
                        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 dark:text-gray-400">{{ __('Categories') }}</h2>
                        <nav class="bg-white rounded-lg shadow divide-y divide-gray-100 overflow-hidden text-sm dark:bg-gray-800 dark:divide-gray-700">
                            {{-- Linki pchlego targu są względne (route(..., false)) i budowane od zera, nie przez fullUrlWithQuery():
                                 tamto zostawiało pusty "?" (/flea-market?), a pełny adres z http:// za serwerem pośredniczącym
                                 hostingu (HTTPS kończone przed PHP) potrafił kończyć się błędem 502. Względny link przeglądarka
                                 dokleja do schematu/domeny, z których faktycznie wczytała stronę. --}}
                            <a href="{{ route('marketplace.index', [], false) }}"
                               @class(['flex justify-between px-4 py-2', 'bg-gray-900 text-white' => ! $categoryId, 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' => $categoryId])>
                                <span>{{ __('All') }}</span>
                                <span @class(['text-gray-400 dark:text-gray-500' => $categoryId])>{{ $totalCount }}</span>
                            </a>
                            @foreach ($categories as $category)
                                <a href="{{ route('marketplace.index', ['category_id' => $category->id], false) }}"
                                   @class(['flex justify-between px-4 py-2', 'bg-gray-900 text-white' => $categoryId === $category->id, 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' => $categoryId !== $category->id])>
                                    <span>{{ $category->name }}</span>
                                    <span @class(['text-gray-400 dark:text-gray-500' => $categoryId !== $category->id])>{{ $category->listings_count }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </aside>
                @endif

                <div class="flex-1 min-w-0 space-y-6">

                    @include('marketplace._contact')

                    <div class="flex items-center justify-between gap-4 flex-wrap">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('Items for sale') }}</h1>

                        <div class="flex rounded-md border border-gray-300 overflow-hidden shrink-0 bg-white dark:bg-gray-800 dark:border-gray-600" role="group" aria-label="{{ __('List view') }}">
                            <a href="{{ route('marketplace.index', array_filter(['category_id' => $categoryId, 'page' => request('page'), 'view' => 'grid']), false) }}"
                               title="{{ __('Grid view') }}"
                               @class(['px-3 py-2 text-sm', 'bg-gray-900 text-white' => $view === 'grid', 'text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700' => $view !== 'grid'])>
                                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path d="M3 3h6v6H3V3zm8 0h6v6h-6V3zM3 11h6v6H3v-6zm8 0h6v6h-6v-6z"/></svg>
                            </a>
                            <a href="{{ route('marketplace.index', array_filter(['category_id' => $categoryId, 'page' => request('page'), 'view' => 'list']), false) }}"
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
                                {{-- Cały wiersz klikalny ("stretched link" na tytule), przycisk OLX/Allegro/Vinted nad nim przez relative z-10. --}}
                                <div class="p-4 flex gap-4 items-start relative hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <div class="w-20 h-20 rounded-md bg-gray-100 overflow-hidden shrink-0 relative dark:bg-gray-700">
                                        @if ($photos->isNotEmpty())
                                            <img src="{{ $photos->first()->url() }}" alt="" class="w-full h-full object-cover">
                                        @else
                                            <x-photo-placeholder size="sm" />
                                        @endif
                                        @include('marketplace._photo-count')
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-baseline justify-between gap-3 flex-wrap">
                                            <h2 class="font-medium text-gray-900 dark:text-gray-100">
                                                <a href="{{ route('marketplace.show', $listing, false) }}" class="hover:underline after:absolute after:inset-0">{{ $listing->title }}</a>
                                            </h2>
                                            <span class="font-semibold text-gray-900 whitespace-nowrap dark:text-gray-100">{{ number_format((float) $listing->price, 2, ',', ' ') }} zł</span>
                                        </div>
                                        @if ($listing->item)
                                            <p class="text-xs text-gray-500 mt-1 dark:text-gray-400">{{ __('Condition') }}: {{ $listing->item->conditionLabel() }}</p>
                                        @endif
                                        <div class="mt-2">
                                            <a href="{{ route('marketplace.show', $listing, false) }}" class="relative z-10 inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">{{ __('More information') }} →</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($listings as $listing)
                                @php $photos = $listing->item?->photosForGallery() ?? collect(); @endphp
                                <div class="bg-white rounded-lg shadow overflow-hidden flex flex-col relative hover:shadow-md transition dark:bg-gray-800">
                                    {{-- Zdjęcie absolute + overflow-hidden, nie zwykłe w-full h-full: w kolumnie flex samo aspect-video jest
                                         tylko "preferowaną" proporcją i wysokie (pionowe) zdjęcie rozpychało ramkę zamiast się przyciąć. --}}
                                    <div class="aspect-video bg-gray-100 relative overflow-hidden shrink-0 dark:bg-gray-700">
                                        @if ($photos->isNotEmpty())
                                            <img src="{{ $photos->first()->url() }}" alt="" class="absolute inset-0 w-full h-full object-cover">
                                        @else
                                            <x-photo-placeholder />
                                        @endif
                                        @include('marketplace._photo-count')
                                    </div>
                                    <div class="p-4 flex-1 flex flex-col">
                                        <h2 class="font-medium text-gray-900 dark:text-gray-100">
                                            <a href="{{ route('marketplace.show', $listing, false) }}" class="hover:underline after:absolute after:inset-0">{{ $listing->title }}</a>
                                        </h2>
                                        <span class="font-semibold text-gray-900 mt-1 dark:text-gray-100">{{ number_format((float) $listing->price, 2, ',', ' ') }} zł</span>
                                        {{-- Celowo bez opisu — na liście tylko zdjęcie, nazwa, cena i stan; opis jest na stronie oferty. --}}
                                        @if ($listing->item)
                                            <p class="text-xs text-gray-500 mt-1 dark:text-gray-400">{{ __('Condition') }}: {{ $listing->item->conditionLabel() }}</p>
                                        @endif
                                        <div class="mt-auto pt-3">
                                            <a href="{{ route('marketplace.show', $listing, false) }}" class="relative z-10 inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">{{ __('More information') }} →</a>
                                        </div>
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
    @endif
</x-marketplace-layout>
