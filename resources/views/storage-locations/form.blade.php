<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $location->exists ? 'Edytuj lokalizację' : 'Nowa lokalizacja' }}</h2>
    </x-slot>

    <div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $location->exists ? route('storage-locations.update', $location) : route('storage-locations.store') }}" class="bg-white rounded-lg shadow p-6 space-y-4">
            @csrf
            @if ($location->exists) @method('PUT') @endif

            <div>
                <x-input-label for="warehouse_id" value="Magazyn" />
                <select id="warehouse_id" name="warehouse_id" class="mt-1 block w-full rounded-md border-gray-300" required>
                    <option value="">— wybierz —</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $location->warehouse_id) == $warehouse->id)>{{ $warehouse->name }} ({{ $warehouse->code }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('warehouse_id')" class="mt-1" />
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <x-input-label for="rack" value="Regał" />
                    <x-text-input id="rack" name="rack" class="mt-1 block w-full" value="{{ old('rack', $location->rack) }}" />
                </div>
                <div>
                    <x-input-label for="shelf" value="Półka" />
                    <x-text-input id="shelf" name="shelf" class="mt-1 block w-full" value="{{ old('shelf', $location->shelf) }}" />
                </div>
                <div>
                    <x-input-label for="bin" value="Pojemnik" />
                    <x-text-input id="bin" name="bin" class="mt-1 block w-full" value="{{ old('bin', $location->bin) }}" />
                </div>
            </div>
            <div>
                <x-input-label for="note" value="Notatka" />
                <x-text-input id="note" name="note" class="mt-1 block w-full" value="{{ old('note', $location->note) }}" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <a href="{{ route('storage-locations.index') }}" class="text-sm text-gray-500 hover:underline">Anuluj</a>
                <x-primary-button>Zapisz</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
