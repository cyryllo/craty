<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Oferta sprzedaży — {{ $item->name }}</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-sky-50 border border-sky-200 text-sky-800 text-sm rounded-md p-4 mb-6">
            OLX nie udostępnia publicznego eksportu masowego dla zwykłych kont — treść poniżej przygotowuje gotowe
            ogłoszenie do ręcznego wystawienia, a przycisk „Eksportuj CSV” na liście ofert zbiera wszystkie
            przygotowane oferty do jednego pliku.
        </div>

        <form method="POST" action="{{ route('items.sale-listing.store', $item) }}" class="bg-white rounded-lg shadow p-6 space-y-4">
            @csrf
            <div>
                <x-input-label for="platform" value="Platforma" />
                <select id="platform" name="platform" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="olx">OLX</option>
                    <option value="allegro">Allegro</option>
                    <option value="inne">Inne</option>
                </select>
            </div>
            <div>
                <x-input-label for="title" value="Tytuł ogłoszenia" />
                <x-text-input id="title" name="title" class="mt-1 block w-full" value="{{ old('title', $listing->title) }}" required />
            </div>
            <div>
                <x-input-label for="description" value="Opis" />
                <textarea id="description" name="description" rows="6" class="mt-1 block w-full rounded-md border-gray-300">{{ old('description', $listing->description) }}</textarea>
            </div>
            <div>
                <x-input-label for="price" value="Cena (zł)" />
                <x-text-input id="price" name="price" type="number" step="0.01" min="0" class="mt-1 block w-full" value="{{ old('price', $listing->price) }}" />
            </div>
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <a href="{{ route('items.show', $item) }}" class="text-sm text-gray-500 hover:underline">Anuluj</a>
                <x-primary-button>Zapisz ofertę</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
