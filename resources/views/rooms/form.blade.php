<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ $room->exists ? __('Edit room') : __('New room') }}</h2>
    </x-slot>

    <div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $room->exists ? route('rooms.update', $room) : route('rooms.store') }}" class="bg-white rounded-lg shadow p-6 space-y-4 dark:bg-gray-800">
            @csrf
            @if ($room->exists) @method('PUT') @endif

            @if ($room->exists)
                <div>
                    <x-input-label :value="__('Warehouse')" />
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $room->warehouse->name }} ({{ $room->warehouse->code }})</p>
                </div>
            @else
                <div>
                    <x-input-label for="warehouse_id" :value="__('Warehouse')" />
                    <select id="warehouse_id" name="warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600" required>
                        <option value="">— {{ __('choose') }} —</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>{{ $warehouse->name }} ({{ $warehouse->code }})</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('warehouse_id')" class="mt-1" />
                </div>
            @endif

            <div>
                <x-input-label for="name" :value="__('Room name')" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name', $room->name) }}" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="code" :value="__('Code')" />
                <x-text-input id="code" name="code" class="mt-1 block w-full font-mono uppercase" value="{{ old('code', $room->code) }}" placeholder="{{ __('e.g.') }} HALA1" required />
                <x-input-error :messages="$errors->get('code')" class="mt-1" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('rooms.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
