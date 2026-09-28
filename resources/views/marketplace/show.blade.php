<x-marketplace-layout :app-name="$appName" :title="$listing->title">
    @php $photos = $listing->item?->photosForGallery() ?? collect(); @endphp

    <div class="space-y-6">
        <a href="{{ $backUrl }}"
           class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
            ← {{ __('Back to all listings') }}
        </a>

        <div class="bg-white rounded-lg shadow overflow-hidden lg:grid lg:grid-cols-2 dark:bg-gray-800">
            {{-- Galeria: duże zdjęcie + miniatury, strzałki gdy zdjęć jest więcej; klik w duże zdjęcie otwiera oryginał. --}}
            <div class="bg-gray-100 dark:bg-gray-900" x-data="{ active: 0, count: {{ $photos->count() }} }"
                 @keydown.left.window="active = (active - 1 + count) % count" @keydown.right.window="active = (active + 1) % count">
                <div class="aspect-square relative">
                    @forelse ($photos as $i => $photo)
                        <a x-show="active === {{ $i }}" @if ($i > 0) x-cloak @endif href="{{ $photo->url() }}" target="_blank" rel="noopener">
                            <img src="{{ $photo->url() }}" alt="{{ $listing->title }}" class="w-full h-full object-contain">
                        </a>
                    @empty
                        <div class="w-full h-full flex items-center justify-center text-gray-400 text-sm dark:text-gray-500">{{ __('No photos') }}</div>
                    @endforelse

                    @if ($photos->count() > 1)
                        <button type="button" @click="active = (active - 1 + count) % count" aria-label="{{ __('Previous photo') }}"
                                class="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-black/50 text-white hover:bg-black/70">‹</button>
                        <button type="button" @click="active = (active + 1) % count" aria-label="{{ __('Next photo') }}"
                                class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-black/50 text-white hover:bg-black/70">›</button>
                        <span class="absolute bottom-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded" x-text="(active + 1) + ' / ' + count"></span>
                    @endif
                </div>

                @if ($photos->count() > 1)
                    <div class="flex gap-2 p-3 overflow-x-auto">
                        @foreach ($photos as $i => $photo)
                            <button type="button" @click="active = {{ $i }}"
                                    class="w-16 h-16 rounded overflow-hidden shrink-0 border-2"
                                    :class="active === {{ $i }} ? 'border-indigo-500' : 'border-transparent opacity-70 hover:opacity-100'">
                                <img src="{{ $photo->url() }}" alt="" class="w-full h-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="p-6 space-y-4">
                <div>
                    @if ($listing->item?->category)
                        <a href="{{ route('marketplace.index', ['category_id' => $listing->item->category_id]) }}"
                           class="text-xs font-semibold uppercase tracking-wide text-indigo-600 hover:underline dark:text-indigo-400">{{ $listing->item->category->name }}</a>
                    @endif
                    <h1 class="text-2xl font-semibold text-gray-900 mt-1 dark:text-gray-100">{{ $listing->title }}</h1>
                    <p class="text-2xl font-bold text-gray-900 mt-2 dark:text-gray-100">{{ number_format((float) $listing->price, 2, ',', ' ') }} zł</p>
                </div>

                @if ($listing->item)
                    <dl class="text-sm">
                        <dt class="inline text-gray-500 dark:text-gray-400">{{ __('Condition') }}:</dt>
                        <dd class="inline text-gray-900 dark:text-gray-100">{{ $listing->item->conditionLabel() }}</dd>
                    </dl>
                @endif

                @if ($listing->description)
                    <p class="text-gray-700 whitespace-pre-line dark:text-gray-300">{{ $listing->description }}</p>
                @endif

                @if ($listing->external_url)
                    <div>@include('marketplace._external-link')</div>
                @endif

                @include('marketplace._contact')
            </div>
        </div>
    </div>
</x-marketplace-layout>
