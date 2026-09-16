<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ $location->exists ? __('Edit location') : __('New location') }}</h2>
    </x-slot>

    <div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $location->exists ? route('storage-locations.update', $location) : route('storage-locations.store') }}" class="bg-white rounded-lg shadow p-6 space-y-4 dark:bg-gray-800">
            @csrf
            @if ($location->exists) @method('PUT') @endif

            <div>
                <x-input-label for="warehouse_id" :value="__('Warehouse')" />
                <select id="warehouse_id" name="warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600" required>
                    <option value="">— {{ __('choose') }} —</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $location->warehouse_id) == $warehouse->id)>{{ $warehouse->name }} ({{ $warehouse->code }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('warehouse_id')" class="mt-1" />
            </div>
            <div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <x-input-label for="rack" :value="__('Rack')" />
                        <x-text-input id="rack" name="rack" class="mt-1 block w-full" value="{{ old('rack', $location->rack) }}" />
                        <x-input-error :messages="$errors->get('rack')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="shelf" :value="__('Shelf')" />
                        <x-text-input id="shelf" name="shelf" class="mt-1 block w-full" value="{{ old('shelf', $location->shelf) }}" />
                        <x-input-error :messages="$errors->get('shelf')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="bin" :value="__('Bin')" />
                        <x-text-input id="bin" name="bin" class="mt-1 block w-full" value="{{ old('bin', $location->bin) }}" />
                        <x-input-error :messages="$errors->get('bin')" class="mt-1" />
                    </div>
                </div>
                <x-input-error :messages="$errors->get('combination')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="note" :value="__('Note')" />
                <x-text-input id="note" name="note" class="mt-1 block w-full" value="{{ old('note', $location->note) }}" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('storage-locations.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
