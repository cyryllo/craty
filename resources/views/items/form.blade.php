<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $item->exists ? __('Edit item') : __('New item') }}
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $item->exists ? route('items.update', $item) : route('items.store') }}"
              enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6 space-y-6">
            @csrf
            @if ($item->exists) @method('PUT') @endif

            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name', $item->name) }}" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="serial_number" :value="__('Serial number (optional)')" />
                    <x-text-input id="serial_number" name="serial_number" class="mt-1 block w-full" value="{{ old('serial_number', $item->serial_number) }}" placeholder="{{ __('assigned by the manufacturer') }}" />
                    <x-input-error :messages="$errors->get('serial_number')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="ean" :value="__('EAN / barcode (optional)')" />
                    <x-text-input id="ean" name="ean" class="mt-1 block w-full" value="{{ old('ean', $item->ean) }}" placeholder="{{ __('e.g.') }} 5901234123457" inputmode="numeric" />
                    <x-input-error :messages="$errors->get('ean')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="category_id" :value="__('Category')" />
                    <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">— {{ __('none') }} —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $item->category_id) == $category->id)>{{ $category->name }} ({{ $category->code }})</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="storage_location_id" :value="__('Location')" />
                    <select id="storage_location_id" name="storage_location_id" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">— {{ __('none') }} —</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected(old('storage_location_id', $item->storage_location_id) == $location->id)>{{ $location->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('storage_location_id')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="value" :value="__('Value (zł)')" />
                    <x-text-input id="value" name="value" type="number" step="0.01" min="0" class="mt-1 block w-full" value="{{ old('value', $item->value) }}" />
                    <x-input-error :messages="$errors->get('value')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="purchased_at" :value="__('Purchase date')" />
                    <x-text-input id="purchased_at" name="purchased_at" type="date" class="mt-1 block w-full" value="{{ old('purchased_at', optional($item->purchased_at)->format('Y-m-d')) }}" />
                    <x-input-error :messages="$errors->get('purchased_at')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="condition" :value="__('Condition')" />
                    <select id="condition" name="condition" class="mt-1 block w-full rounded-md border-gray-300" required>
                        @foreach (\App\Models\Item::CONDITIONS as $value => $label)
                            <option value="{{ $value }}" @selected(old('condition', $item->condition ?: 'uzywany') == $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300" required>
                        @foreach (\App\Models\Item::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $item->status ?: 'dostepny') == $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="description" :value="__('Description')" />
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300">{{ old('description', $item->description) }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="specification" :value="__('Technical specification')" />
                    <textarea id="specification" name="specification" rows="3" class="mt-1 block w-full rounded-md border-gray-300" placeholder="{{ __('model, parameters, serial number...') }}">{{ old('specification', $item->specification) }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="photos" :value="__('Photos')" />
                    <input id="photos" name="photos[]" type="file" accept="image/*" multiple class="mt-1 block w-full text-sm">
                    @if ($item->exists && $item->photos->isNotEmpty())
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($item->photos as $photo)
                                <img src="{{ $photo->url() }}" class="w-20 h-20 object-cover rounded-md border {{ $photo->is_primary ? 'ring-2 ring-indigo-500' : '' }}">
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="attachments" :value="__('Attachments (invoice, manual...)')" />
                    <input id="attachments" name="attachments[]" type="file" multiple class="mt-1 block w-full text-sm">
                    @if ($item->exists && $item->attachments->isNotEmpty())
                        <ul class="mt-2 text-sm text-gray-600 list-disc list-inside">
                            @foreach ($item->attachments as $attachment)
                                <li><a href="{{ $attachment->url() }}" class="text-indigo-600 hover:underline" target="_blank">{{ $attachment->label }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <a href="{{ $item->exists ? route('items.show', $item) : route('items.index') }}" class="text-sm text-gray-500 hover:underline">{{ __('Cancel') }}</a>
                <x-primary-button>{{ $item->exists ? __('Save changes') : __('Add to inventory') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
