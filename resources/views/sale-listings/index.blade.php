<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sprzedaż</h2>
            @if ($draft->isNotEmpty())
                <a href="{{ route('sale-listings.export') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    Eksportuj CSV ({{ $draft->count() }})
                </a>
            @endif
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">

        <div class="bg-sky-50 border border-sky-200 text-sky-800 text-sm rounded-md p-4">
            OLX nie udostępnia publicznego eksportu masowego dla zwykłych kont — plik CSV poniżej zawiera
            gotowe tytuły/opisy do ręcznego wystawienia albo do wgrania w narzędziu pośredniczącym
            (np. BaseLinker). Ofertę dla kolejnego przedmiotu przygotowujesz na jego karcie, przyciskiem
            „Przygotuj ofertę sprzedaży”.
        </div>

        <div>
            <h3 class="font-medium text-gray-900 mb-3">Przygotowane, jeszcze nie wyeksportowane ({{ $draft->count() }})</h3>
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Przedmiot</th>
                            <th class="text-left px-4 py-3">Tytuł oferty</th>
                            <th class="text-left px-4 py-3">Cena</th>
                            <th class="text-left px-4 py-3">Platforma</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($draft as $listing)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('items.show', $listing->item) }}" class="text-gray-900 hover:underline">{{ $listing->item->name }}</a>
                                    <div class="text-xs font-mono text-gray-400">{{ $listing->item->inventory_no }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $listing->title }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $listing->price ? number_format((float) $listing->price, 2, ',', ' ').' zł' : '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 uppercase text-xs">{{ $listing->platform }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('items.sale-listing.create', $listing->item) }}" class="text-indigo-600 hover:underline">edytuj treść</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                    Brak przygotowanych ofert. Wejdź na kartę przedmiotu i kliknij „Przygotuj ofertę sprzedaży”.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($exported->isNotEmpty())
            <div>
                <h3 class="font-medium text-gray-900 mb-3">Ostatnio wyeksportowane</h3>
                <div class="bg-white rounded-lg shadow overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                            <tr>
                                <th class="text-left px-4 py-3">Przedmiot</th>
                                <th class="text-left px-4 py-3">Tytuł oferty</th>
                                <th class="text-left px-4 py-3">Cena</th>
                                <th class="text-left px-4 py-3">Wyeksportowano</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($exported as $listing)
                                <tr>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('items.show', $listing->item) }}" class="text-gray-900 hover:underline">{{ $listing->item->name }}</a>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ $listing->title }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $listing->price ? number_format((float) $listing->price, 2, ',', ' ').' zł' : '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $listing->exported_at?->format('d.m.Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
