<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Quick add') }}</h2>
    </x-slot>

    <div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <p class="text-sm text-gray-500 mb-4 dark:text-gray-400">
            {{ __('The scanned code doesn\'t match any item yet. Fill in what you can now — category, location, condition and description are all optional here, and anything left blank can be completed later from the item\'s own edit form.') }}
        </p>

        <form method="POST" action="{{ route('scan.quick-add.store') }}" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6 space-y-4 dark:bg-gray-800">
            @csrf

            <div>
                <x-input-label for="code" :value="__('Scanned code')" />
                <x-text-input id="code" name="code" class="mt-1 block w-full" value="{{ old('code', $code) }}" required />
                <x-input-error :messages="$errors->get('code')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="item_name" :value="__('Item name')" />
                <x-text-input id="item_name" name="item_name" class="mt-1 block w-full" value="{{ old('item_name') }}" autocomplete="off" required autofocus />
                <x-input-error :messages="$errors->get('item_name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="photo" :value="__('Photo (optional)')" />
                <input id="photo" name="photo" type="file" accept="image/*" capture="environment" class="mt-1 block w-full text-sm">
                <x-input-error :messages="$errors->get('photo')" class="mt-1" />
            </div>

            <div class="grid sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100 dark:border-gray-700">
                <div>
                    <x-input-label for="category_id" :value="__('Category')" />
                    <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600">
                        <option value="">— {{ __('none') }} —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }} ({{ $category->code }})</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="storage_location_id" :value="__('Location')" />
                    <select id="storage_location_id" name="storage_location_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600">
                        <option value="">— {{ __('none') }} —</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected(old('storage_location_id') == $location->id)>{{ $location->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('storage_location_id')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="condition" :value="__('Condition')" />
                    <select id="condition" name="condition" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600">
                        @foreach (\App\Models\Item::CONDITIONS as $value => $label)
                            <option value="{{ $value }}" @selected(old('condition', 'uzywany') == $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('condition')" class="mt-1" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="description" :value="__('Description')" />
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600" placeholder="{{ __('model, parameters, condition, anything worth noting...') }}">{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('scan.show') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Add') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
