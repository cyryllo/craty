<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Sale') }}</h2>
            @if ($draft->isNotEmpty())
                <a href="{{ route('sale-listings.export') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    {{ __('Export CSV') }} ({{ $draft->count() }})
                </a>
            @endif
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8"
         x-data="{
            open: false,
            listing: null,
            listings: @js($draft->map(fn ($l) => [
                'id' => $l->id,
                'item' => $l->item->name,
                'inventoryNo' => $l->item->inventory_no,
                'title' => $l->title,
                'description' => $l->description ?? '',
                'price' => $l->price ? number_format((float) $l->price, 2, ',', ' ').' zł' : '',
                'platform' => strtoupper($l->platform),
                'markListedUrl' => route('sale-listings.mark-listed', $l),
                'photos' => $l->item->photos->map(fn ($p) => $p->url())->values()->all(),
            ])),
            copied: null,
            show(id) {
                this.listing = this.listings.find(l => l.id === id) ?? null;
                this.open = true;
                this.copied = null;
            },
            async copy(field) {
                if (! this.listing) return;
                try {
                    await navigator.clipboard.writeText(this.listing[field] ?? '');
                    this.copied = field;
                    setTimeout(() => { if (this.copied === field) this.copied = null; }, 1500);
                } catch (e) {}
            },
         }">

        @include('sale-listings._tabs', ['active' => 'draft'])

        <div class="bg-white rounded-lg shadow overflow-x-auto dark:bg-gray-800">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase dark:bg-gray-900 dark:text-gray-400">
                    <tr>
                        <th class="text-left px-4 py-3">{{ __('Item') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Listing title') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Price') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Platform') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($draft as $listing)
                        <tr class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700" @click="show({{ $listing->id }})">
                            <td class="px-4 py-3">
                                <a href="{{ route('items.show', $listing->item) }}" @click.stop class="text-gray-900 hover:underline dark:text-gray-100">{{ $listing->item->name }}</a>
                                <div class="text-xs font-mono text-gray-400 dark:text-gray-500">{{ $listing->item->inventory_no }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $listing->title }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $listing->price ? number_format((float) $listing->price, 2, ',', ' ').' zł' : '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 uppercase text-xs dark:text-gray-400">{{ $listing->platform }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('items.sale-listing.create', $listing->item) }}" @click.stop class="text-indigo-600 hover:underline dark:text-indigo-400">{{ __('edit content') }}</a>
                                <form method="POST" action="{{ route('sale-listings.mark-listed', $listing) }}" @click.stop onsubmit="return confirm('{{ __('Mark as listed?') }}');" class="inline ms-3">
                                    @csrf
                                    <button class="text-emerald-700 hover:underline dark:text-emerald-400">{{ __('list for sale') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                {{ __('No prepared listings yet. Go to an item page and click "Prepare sale listing".') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Popup z podglądem oferty i przyciskami kopiowania — do szybkiego ręcznego wystawienia na OLX/Allegro bez pobierania CSV. --}}
        <div x-show="open" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50" @click.self="open = false" @keydown.escape.window="open = false">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto dark:bg-gray-800">
                <template x-if="listing">
                    <div class="space-y-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="font-medium text-gray-900 dark:text-gray-100" x-text="listing.item"></h3>
                                <p class="text-xs font-mono text-gray-400 dark:text-gray-500" x-text="listing.inventoryNo"></p>
                            </div>
                            <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none dark:text-gray-500">&times;</button>
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <x-input-label :value="__('Listing title')" />
                                <button type="button" @click="copy('title')" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">
                                    <span x-show="copied !== 'title'">{{ __('Copy') }}</span>
                                    <span x-show="copied === 'title'" x-cloak>{{ __('Copied!') }}</span>
                                </button>
                            </div>
                            <p class="mt-1 text-sm text-gray-800 border border-gray-200 rounded-md px-3 py-2 bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:border-gray-700" x-text="listing.title"></p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <x-input-label :value="__('Price')" />
                                <button type="button" @click="copy('price')" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">
                                    <span x-show="copied !== 'price'">{{ __('Copy') }}</span>
                                    <span x-show="copied === 'price'" x-cloak>{{ __('Copied!') }}</span>
                                </button>
                            </div>
                            <p class="mt-1 text-sm text-gray-800 border border-gray-200 rounded-md px-3 py-2 bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:border-gray-700" x-text="listing.price || '—'"></p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <x-input-label :value="__('Description')" />
                                <button type="button" @click="copy('description')" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">
                                    <span x-show="copied !== 'description'">{{ __('Copy') }}</span>
                                    <span x-show="copied === 'description'" x-cloak>{{ __('Copied!') }}</span>
                                </button>
                            </div>
                            <p class="mt-1 text-sm text-gray-800 border border-gray-200 rounded-md px-3 py-2 bg-gray-50 whitespace-pre-line dark:bg-gray-900 dark:text-gray-200 dark:border-gray-700" x-text="listing.description || '—'"></p>
                        </div>

                        <div x-show="listing.photos.length" x-cloak>
                            <x-input-label :value="__('Photos')" />
                            <div class="mt-1 flex flex-wrap gap-2">
                                <template x-for="photo in listing.photos" :key="photo">
                                    <a :href="photo" download class="block group relative">
                                        <img :src="photo" class="w-16 h-16 object-cover rounded-md border border-gray-200 dark:border-gray-700">
                                        <span class="absolute inset-0 flex items-center justify-center bg-black/40 text-white text-xs opacity-0 group-hover:opacity-100 rounded-md">{{ __('Download') }}</span>
                                    </a>
                                </template>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" @click="open = false" class="text-sm text-gray-500 hover:underline dark:text-gray-400">{{ __('Close') }}</button>
                            <form :action="listing.markListedUrl" method="POST" @submit="open = false">
                                @csrf
                                <button class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">{{ __('Mark as listed') }}</button>
                            </form>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>
