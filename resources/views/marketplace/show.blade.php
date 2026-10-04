<x-marketplace-layout :app-name="$appName" :title="$listing->title">
    @php $photos = $listing->item?->photosForGallery() ?? collect(); @endphp

    <div class="space-y-6">
        <a href="{{ $backUrl }}"
           class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
            ← {{ __('Back to all listings') }}
        </a>

        <div class="bg-white rounded-lg shadow overflow-hidden lg:grid lg:grid-cols-2 lg:items-start dark:bg-gray-800">
            {{-- Galeria jak na stronie przedmiotu w panelu: siatka kwadratowych, przyciętych miniatur; klik otwiera nieprzycięty oryginał. --}}
            <div class="p-4">
                @if ($photos->isNotEmpty())
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ($photos as $photo)
                            <a href="{{ $photo->url() }}" target="_blank" rel="noopener" class="block">
                                <img src="{{ $photo->url() }}" alt="{{ $listing->title }}" class="aspect-square object-cover rounded-md w-full hover:opacity-90">
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="aspect-[4/3] rounded-md bg-gray-100 flex items-center justify-center text-gray-400 text-sm dark:bg-gray-900 dark:text-gray-500">{{ __('No photos') }}</div>
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
