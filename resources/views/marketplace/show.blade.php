<x-marketplace-layout :app-name="$appName" :title="$listing->title">
    @php $photos = $listing->item?->photosForGallery() ?? collect(); @endphp

    <div class="space-y-6">
        <a href="{{ $backUrl }}"
           class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
            ← {{ __('Back to all listings') }}
        </a>

        <div class="bg-white rounded-lg shadow overflow-hidden lg:grid lg:grid-cols-2 lg:items-start dark:bg-gray-800">
            {{-- Galeria jak na stronie przedmiotu w panelu: siatka kwadratowych, przyciętych miniatur. Klik otwiera
                 okienko (lightbox) z pełnym zdjęciem i przełączaniem; href zostaje jako zapas bez JavaScriptu. --}}
            <div class="p-4"
                 x-data="{
                    photos: @js($photos->map(fn ($p) => $p->url())->values()),
                    open: false,
                    index: 0,
                    show(i) { this.index = i; this.open = true; },
                    next() { this.index = (this.index + 1) % this.photos.length; },
                    prev() { this.index = (this.index - 1 + this.photos.length) % this.photos.length; },
                 }"
                 @keydown.escape.window="open = false"
                 @keydown.arrow-right.window="open && next()"
                 @keydown.arrow-left.window="open && prev()">
                @if ($photos->isNotEmpty())
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ($photos as $i => $photo)
                            <a href="{{ $photo->url() }}" @click.prevent="show({{ $i }})" class="block" aria-label="{{ __('Open photo :n of :total', ['n' => $i + 1, 'total' => $photos->count()]) }}">
                                <img src="{{ $photo->url() }}" alt="{{ $listing->title }}" class="aspect-square object-cover rounded-md w-full hover:opacity-90">
                            </a>
                        @endforeach
                    </div>

                    {{-- Lightbox: przyciemnione tło, zdjęcie w całości (object-contain), strzałki, licznik, miniatury. --}}
                    <div x-show="open" x-cloak x-transition.opacity
                         class="fixed inset-0 z-50 flex flex-col bg-black/90"
                         role="dialog" aria-modal="true" aria-label="{{ $listing->title }}"
                         @click.self="open = false">
                        <div class="flex items-center justify-between px-4 py-3 text-white">
                            <span class="text-sm tabular-nums" x-text="(index + 1) + ' / ' + photos.length"></span>
                            <button type="button" @click="open = false" class="w-11 h-11 flex items-center justify-center rounded-full hover:bg-white/10" aria-label="{{ __('Close') }}">
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                            </button>
                        </div>

                        {{-- Marginesy wokół zdjęcia: po bokach miejsce na strzałki (px-16/px-24), od dołu odstęp od miniatur,
                             a samo zdjęcie maks. wysokość ekranu minus pasek u góry, miniatury i margines od dołu (14rem; dvh, żeby na
                             telefonie pasek adresu nie zjadał dołu), 85% szerokości, nigdy powiększane ponad oryginał. --}}
                        <div class="relative flex-1 min-h-0 flex items-center justify-center px-16 sm:px-24 py-4 sm:py-8" @click.self="open = false">
                            <img :src="photos[index]" alt="{{ $listing->title }}" class="max-h-[calc(100dvh-14rem)] max-w-[85vw] sm:max-w-[min(85vw,1200px)] w-auto h-auto object-contain rounded-md shadow-2xl select-none">

                            <template x-if="photos.length > 1">
                                <div>
                                    <button type="button" @click="prev()" class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20" aria-label="{{ __('Previous photo') }}">
                                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                                    </button>
                                    <button type="button" @click="next()" class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20" aria-label="{{ __('Next photo') }}">
                                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <div x-show="photos.length > 1" class="flex justify-center gap-2 overflow-x-auto px-4 pt-2 pb-8">
                            <template x-for="(photo, i) in photos" :key="i">
                                <button type="button" @click="index = i" class="w-14 h-14 shrink-0 rounded overflow-hidden border-2"
                                        :class="i === index ? 'border-white' : 'border-transparent opacity-60 hover:opacity-100'"
                                        :aria-label="'{{ __('Photo') }} ' + (i + 1)">
                                    <img :src="photo" alt="" class="w-full h-full object-cover">
                                </button>
                            </template>
                        </div>
                    </div>
                @else
                    <div class="relative aspect-[4/3] rounded-md overflow-hidden"><x-photo-placeholder size="lg" /></div>
                @endif
            </div>

            <div class="p-6 space-y-4">
                <div>
                    @if ($listing->item?->category)
                        <a href="{{ route('marketplace.index', ['category_id' => $listing->item->category_id], false) }}"
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

                {{-- Jak kupić (link do ogłoszenia + kontakt) od razu pod stanem technicznym, a dopiero
                     potem pełny opis — żeby przy długim opisie nie trzeba było przewijać do kontaktu. --}}
                @if ($listing->external_url)
                    <div>@include('marketplace._external-link')</div>
                @endif

                @include('marketplace._contact')

                @if ($listing->description)
                    <p class="text-gray-700 whitespace-pre-line dark:text-gray-300">{{ $listing->description }}</p>
                @endif
            </div>
        </div>
    </div>
</x-marketplace-layout>
