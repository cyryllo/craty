<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ $contact->exists ? __('Edit contact') : __('New contact') }}</h2>
    </x-slot>

    <div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <form method="POST" action="{{ $contact->exists ? route('contacts.update', $contact) : route('contacts.store') }}" class="bg-white rounded-lg shadow p-6 space-y-4 dark:bg-gray-800">
            @csrf
            @if ($contact->exists) @method('PUT') @endif

            <div>
                <x-input-label for="name" :value="__('Name (person or company)')" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name', $contact->name) }}" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="type" :value="__('Type')" />
                <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600">
                    @foreach (\App\Models\Contact::TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $contact->type) === $value)>{{ __($label) }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('type')" class="mt-1" />
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="phone" :value="__('Phone')" />
                    <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" value="{{ old('phone', $contact->phone) }}" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email', $contact->email) }}" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
            </div>
            <div>
                <x-input-label for="address" :value="__('Address (printed on protocols)')" />
                <textarea id="address" name="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600">{{ old('address', $contact->address) }}</textarea>
                <x-input-error :messages="$errors->get('address')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="notes" :value="__('Notes')" />
                <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600">{{ old('notes', $contact->notes) }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-1" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ $contact->exists ? route('contacts.show', $contact) : route('contacts.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>

        @if ($contact->exists)
            <form method="POST" action="{{ route('contacts.destroy', $contact) }}" onsubmit="return confirm('{{ __('Delete this contact? Its loans stay in the history under the same name.') }}');" class="text-right">
                @csrf @method('DELETE')
                <button class="text-sm text-red-600 hover:underline dark:text-red-400">{{ __('Delete contact') }}</button>
            </form>
        @endif
    </div>
</x-app-layout>
