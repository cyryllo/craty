<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $item->exists ? 'Edytuj przedmiot' : 'Nowy przedmiot' }}
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $item->exists ? route('items.update', $item) : route('items.store') }}"
              enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6 space-y-6">
            @csrf
            @if ($item->exists) @method('PUT') @endif

            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <x-input-label for="name" value="Nazwa" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name', $item->name) }}" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="serial_number" value="Numer seryjny (opcjonalnie)" />
                    <x-text-input id="serial_number" name="serial_number" class="mt-1 block w-full" value="{{ old('serial_number', $item->serial_number) }}" placeholder="nadany przez producenta" />
                    <x-input-error :messages="$errors->get('serial_number')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="ean" value="Kod EAN / kreskowy (opcjonalnie)" />
                    <x-text-input id="ean" name="ean" class="mt-1 block w-full" value="{{ old('ean', $item->ean) }}" placeholder="np. 5901234123457" inputmode="numeric" />
                    <x-input-error :messages="$errors->get('ean')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="category_id" value="Kategoria" />
                    <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">— brak —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $item->category_id) == $category->id)>{{ $category->name }} ({{ $category->code }})</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="storage_location_id" value="Lokalizacja" />
                    <select id="storage_location_id" name="storage_location_id" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">— brak —</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected(old('storage_location_id', $item->storage_location_id) == $location->id)>{{ $location->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('storage_location_id')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="value" value="Wartość (zł)" />
                    <x-text-input id="value" name="value" type="number" step="0.01" min="0" class="mt-1 block w-full" value="{{ old('value', $item->value) }}" />
                    <x-input-error :messages="$errors->get('value')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="purchased_at" value="Data zakupu" />
                    <x-text-input id="purchased_at" name="purchased_at" type="date" class="mt-1 block w-full" value="{{ old('purchased_at', optional($item->purchased_at)->format('Y-m-d')) }}" />
                    <x-input-error :messages="$errors->get('purchased_at')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="condition" value="Stan techniczny" />
                    <select id="condition" name="condition" class="mt-1 block w-full rounded-md border-gray-300" required>
                        @foreach (\App\Models\Item::CONDITIONS as $value => $label)
                            <option value="{{ $value }}" @selected(old('condition', $item->condition ?: 'uzywany') == $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300" required>
                        @foreach (\App\Models\Item::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $item->status ?: 'dostepny') == $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="description" value="Opis" />
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300">{{ old('description', $item->description) }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="specification" value="Specyfikacja techniczna" />
                    <textarea id="specification" name="specification" rows="3" class="mt-1 block w-full rounded-md border-gray-300" placeholder="model, parametry, numer seryjny...">{{ old('specification', $item->specification) }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="photos" value="Zdjęcia" />
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
                    <x-input-label for="attachments" value="Załączniki (faktura, instrukcja...)" />
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
                <a href="{{ $item->exists ? route('items.show', $item) : route('items.index') }}" class="text-sm text-gray-500 hover:underline">Anuluj</a>
                <x-primary-button>{{ $item->exists ? 'Zapisz zmiany' : 'Dodaj do ewidencji' }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
