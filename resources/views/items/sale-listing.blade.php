<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Sale listing') }} — {{ $item->name }}</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-sky-50 border border-sky-200 text-sky-800 text-sm rounded-md p-4 mb-6 dark:bg-sky-950 dark:text-sky-300 dark:border-sky-800">
            {{ __('OLX does not offer a public bulk-listing export for regular accounts — the content below prepares a ready-made listing for manual posting, and the "Export CSV" button on the listings page collects all prepared listings into one file.') }}
        </div>

        {{-- Jeden widok na tworzenie (create) i edycję (edit) — zapisana oferta ma id, nowa z podpowiedziami nie. --}}
        <form method="POST" action="{{ $listing->exists ? route('sale-listings.update', $listing) : route('items.sale-listing.store', $item) }}" class="bg-white rounded-lg shadow p-6 space-y-4 dark:bg-gray-800">
            @csrf
            @if ($listing->exists)
                @method('PUT')
            @endif
            <div>
                <x-input-label for="platform" :value="__('Platform')" />
                <select id="platform" name="platform" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600">
                    @foreach (\App\Models\SaleListing::PLATFORMS as $value => $label)
                        <option value="{{ $value }}" @selected(old('platform', $listing->platform) === $value)>{{ __($label) }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('platform')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="title" :value="__('Listing title')" />
                <x-text-input id="title" name="title" class="mt-1 block w-full" value="{{ old('title', $listing->title) }}" required />
            </div>
            <div>
                <x-input-label for="description" :value="__('Description')" />
                <textarea id="description" name="description" rows="6" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600">{{ old('description', $listing->description) }}</textarea>
            </div>
            <div>
                <x-input-label for="price" :value="__('Price (zł)')" />
                <x-text-input id="price" name="price" type="number" step="0.01" min="0" class="mt-1 block w-full" value="{{ old('price', $listing->price) }}" />
            </div>
            <div>
                <x-input-label for="external_url" :value="__('Link to the listing on OLX / Allegro (optional)')" />
                <x-text-input id="external_url" name="external_url" type="url" placeholder="https://" class="mt-1 block w-full" value="{{ old('external_url', $listing->external_url) }}" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('If provided, the public flea market shows a button leading to this listing.') }}</p>
                <x-input-error :messages="$errors->get('external_url')" class="mt-2" />
            </div>
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ $listing->exists ? url()->previous(route('sale-listings.index')) : route('items.show', $item) }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save listing') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
