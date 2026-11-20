<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ $consumable->exists ? __('Edit consumable') : __('New consumable') }}</h2>
    </x-slot>

    @php($field = 'mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600')
    <div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <form method="POST" action="{{ $consumable->exists ? route('consumables.update', $consumable) : route('consumables.store') }}" class="bg-white rounded-lg shadow p-6 space-y-4 dark:bg-gray-800">
            @csrf
            @if ($consumable->exists) @method('PUT') @endif

            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name', $consumable->name) }}" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="unit" :value="__('Unit')" />
                    <select id="unit" name="unit" class="{{ $field }}">
                        @foreach (\App\Models\Consumable::UNITS as $value => $label)
                            <option value="{{ $value }}" @selected(old('unit', $consumable->unit) === $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="min_quantity" :value="__('Minimum stock')" />
                    <input id="min_quantity" name="min_quantity" type="number" step="any" min="0" value="{{ old('min_quantity', $consumable->min_quantity !== null ? \App\Models\Consumable::formatQuantity($consumable->min_quantity) : '') }}" class="{{ $field }}">
                    <x-input-error :messages="$errors->get('min_quantity')" class="mt-1" />
                </div>
            </div>
            @unless ($consumable->exists)
                <div>
                    <x-input-label for="quantity" :value="__('Quantity in stock now')" />
                    <input id="quantity" name="quantity" type="number" step="any" min="0" value="{{ old('quantity', 0) }}" class="{{ $field }}">
                    <x-input-error :messages="$errors->get('quantity')" class="mt-1" />
                </div>
            @endunless
            <div>
                <x-input-label for="storage_location_id" :value="__('Location')" />
                <select id="storage_location_id" name="storage_location_id" class="{{ $field }}">
                    <option value="">— {{ __('none') }} —</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" @selected(old('storage_location_id', $consumable->storage_location_id) == $location->id)>{{ $location->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="category_id" :value="__('Category')" />
                <select id="category_id" name="category_id" class="{{ $field }}">
                    <option value="">— {{ __('none') }} —</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $consumable->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="notes" :value="__('Notes')" />
                <textarea id="notes" name="notes" rows="3" class="{{ $field }}">{{ old('notes', $consumable->notes) }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ $consumable->exists ? route('consumables.show', $consumable) : route('consumables.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>

        @if ($consumable->exists)
            <form method="POST" action="{{ route('consumables.destroy', $consumable) }}" onsubmit="return confirm('{{ __('Delete this consumable and its history?') }}');" class="text-right">
                @csrf @method('DELETE')
                <button class="text-sm text-red-600 hover:underline dark:text-red-400">{{ __('Delete consumable') }}</button>
            </form>
        @endif
    </div>
</x-app-layout>
