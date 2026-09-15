<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Sale') }}</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8"
         x-data="{
            open: false,
            listing: null,
            listings: @js($exported->map(fn ($l) => [
                'id' => $l->id,
                'item' => $l->item->name,
                'inventoryNo' => $l->item->inventory_no,
                'title' => $l->title,
                'description' => $l->description ?? '',
                'price' => $l->price ? number_format((float) $l->price, 2, ',', ' ').' zł' : '',
                'platform' => strtoupper($l->platform),
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

        @include('sale-listings._tabs', ['active' => 'exported'])

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">{{ __('Item') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Listing title') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Price') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Exported') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($exported as $listing)
                        <tr class="cursor-pointer hover:bg-gray-50" @click="show({{ $listing->id }})">
                            <td class="px-4 py-3">
                                <a href="{{ route('items.show', $listing->item) }}" @click.stop class="text-gray-900 hover:underline">{{ $listing->item->name }}</a>
                                <div class="text-xs font-mono text-gray-400">{{ $listing->item->inventory_no }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $listing->title }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $listing->price ? number_format((float) $listing->price, 2, ',', ' ').' zł' : '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $listing->exported_at?->format('d.m.Y H:i') }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <form method="POST" action="{{ route('sale-listings.mark-sold', $listing) }}" @click.stop onsubmit="return confirm('{{ __('Mark as sold?') }}');" class="inline">
                                    @csrf
                                    <button class="text-emerald-700 hover:underline">{{ __('mark as sold') }}</button>
                                </form>
                                <form method="POST" action="{{ route('sale-listings.withdraw', $listing) }}" @click.stop onsubmit="return confirm('{{ __('Withdraw this listing from sale?') }}');" class="inline ms-3">
                                    @csrf
                                    <button class="text-red-600 hover:underline">{{ __('withdraw') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                {{ __('Nothing listed yet — export prepared listings from the "Prepared" tab.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $exported->links() }}

        {{-- Ten sam popup do podglądu/kopiowania co na "Przygotowane" — przydaje się np. żeby skopiować treść i wrzucić to samo ogłoszenie na drugi portal. Bez przycisku "wystaw", bo ta oferta już jest wystawiona. --}}
        <div x-show="open" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50" @click.self="open = false" @keydown.escape.window="open = false">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <template x-if="listing">
                    <div class="space-y-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="font-medium text-gray-900" x-text="listing.item"></h3>
                                <p class="text-xs font-mono text-gray-400" x-text="listing.inventoryNo"></p>
                            </div>
                            <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <x-input-label :value="__('Listing title')" />
                                <button type="button" @click="copy('title')" class="text-xs text-indigo-600 hover:underline">
                                    <span x-show="copied !== 'title'">{{ __('Copy') }}</span>
                                    <span x-show="copied === 'title'" x-cloak>{{ __('Copied!') }}</span>
                                </button>
                            </div>
                            <p class="mt-1 text-sm text-gray-800 border border-gray-200 rounded-md px-3 py-2 bg-gray-50" x-text="listing.title"></p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <x-input-label :value="__('Price')" />
                                <button type="button" @click="copy('price')" class="text-xs text-indigo-600 hover:underline">
                                    <span x-show="copied !== 'price'">{{ __('Copy') }}</span>
                                    <span x-show="copied === 'price'" x-cloak>{{ __('Copied!') }}</span>
                                </button>
                            </div>
                            <p class="mt-1 text-sm text-gray-800 border border-gray-200 rounded-md px-3 py-2 bg-gray-50" x-text="listing.price || '—'"></p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <x-input-label :value="__('Description')" />
                                <button type="button" @click="copy('description')" class="text-xs text-indigo-600 hover:underline">
                                    <span x-show="copied !== 'description'">{{ __('Copy') }}</span>
                                    <span x-show="copied === 'description'" x-cloak>{{ __('Copied!') }}</span>
                                </button>
                            </div>
                            <p class="mt-1 text-sm text-gray-800 border border-gray-200 rounded-md px-3 py-2 bg-gray-50 whitespace-pre-line" x-text="listing.description || '—'"></p>
                        </div>

                        <div x-show="listing.photos.length" x-cloak>
                            <x-input-label :value="__('Photos')" />
                            <div class="mt-1 flex flex-wrap gap-2">
                                <template x-for="photo in listing.photos" :key="photo">
                                    <a :href="photo" download class="block group relative">
                                        <img :src="photo" class="w-16 h-16 object-cover rounded-md border border-gray-200">
                                        <span class="absolute inset-0 flex items-center justify-center bg-black/40 text-white text-xs opacity-0 group-hover:opacity-100 rounded-md">{{ __('Download') }}</span>
                                    </a>
                                </template>
                            </div>
                        </div>

                        <div class="flex items-center justify-end pt-2 border-t border-gray-100">
                            <button type="button" @click="open = false" class="text-sm text-gray-500 hover:underline">{{ __('Close') }}</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>
