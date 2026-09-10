<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Przedmioty</h2>
            @if (auth()->user()->isMagazynier())
                <a href="{{ route('items.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    + Dodaj przedmiot
                </a>
            @endif
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

        <form method="GET" class="bg-white rounded-lg shadow p-4 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Szukaj</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="nazwa, nr ewidencyjny, seryjny albo EAN"
                       class="w-full rounded-md border-gray-300 text-sm">
            </div>
            <div class="min-w-[160px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Kategoria</label>
                <select name="category_id" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">wszystkie</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? null) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[160px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="status" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">wszystkie</option>
                    @foreach (\App\Models\Item::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200">Filtruj</button>
            @if (array_filter($filters))
                <a href="{{ route('items.index') }}" class="text-sm text-gray-500 hover:underline">wyczyść</a>
            @endif
        </form>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse ($items as $item)
                <a href="{{ route('items.show', $item) }}" class="bg-white rounded-lg shadow hover:shadow-md transition overflow-hidden flex flex-col">
                    <div class="aspect-[4/3] bg-gray-100 flex items-center justify-center overflow-hidden">
                        @if ($item->primaryPhoto->first())
                            <img src="{{ $item->primaryPhoto->first()->url() }}" alt="" class="w-full h-full object-cover">
                        @else
                            <span class="text-gray-300 text-sm">brak zdjęcia</span>
                        @endif
                    </div>
                    <div class="p-4 flex-1 flex flex-col gap-1">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-medium text-gray-900 leading-snug">{{ $item->name }}</h3>
                            <span @class([
                                'shrink-0 text-xs font-medium px-2 py-0.5 rounded-full',
                                'bg-emerald-100 text-emerald-700' => $item->status === 'dostepny',
                                'bg-amber-100 text-amber-700' => $item->status === 'wypozyczony',
                                'bg-red-100 text-red-700' => $item->status === 'w_naprawie',
                                'bg-sky-100 text-sky-700' => $item->status === 'do_sprzedazy',
                                'bg-gray-100 text-gray-600' => in_array($item->status, ['sprzedany', 'wycofany']),
                            ])>{{ $item->statusLabel() }}</span>
                        </div>
                        <p class="text-xs font-mono text-gray-400">{{ $item->inventory_no }}</p>
                        <p class="text-sm text-gray-500 mt-auto pt-2">
                            {{ $item->category?->name ?? 'bez kategorii' }} · {{ $item->storageLocation?->label() ?? 'bez lokalizacji' }}
                        </p>
                    </div>
                </a>
            @empty
                <div class="col-span-full bg-white rounded-lg shadow p-8 text-center text-gray-500">
                    Nic nie znaleziono. @if (auth()->user()->isMagazynier())<a href="{{ route('items.create') }}" class="text-indigo-600 hover:underline">Dodaj pierwszy przedmiot</a>.@endif
                </div>
            @endforelse
        </div>

        {{ $items->links() }}
    </div>
</x-app-layout>
