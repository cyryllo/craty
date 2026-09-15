<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Quick add') }}</h2>
    </x-slot>

    <div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <p class="text-sm text-gray-500 mb-4">
            {{ __('The scanned code doesn\'t match any item yet. Add it in a few seconds — you can fill in the category, location, and the rest later from the item\'s own edit form.') }}
        </p>

        <form method="POST" action="{{ route('scan.quick-add.store') }}" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6 space-y-4">
            @csrf

            <div>
                <x-input-label for="code" :value="__('Scanned code')" />
                <x-text-input id="code" name="code" class="mt-1 block w-full" value="{{ old('code', $code) }}" required />
                <x-input-error :messages="$errors->get('code')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="item_name" :value="__('Name')" />
                <x-text-input id="item_name" name="item_name" class="mt-1 block w-full" value="{{ old('item_name') }}" autocomplete="off" required autofocus />
                <x-input-error :messages="$errors->get('item_name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="photo" :value="__('Photo (optional)')" />
                <input id="photo" name="photo" type="file" accept="image/*" capture="environment" class="mt-1 block w-full text-sm">
                <x-input-error :messages="$errors->get('photo')" class="mt-1" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <a href="{{ route('scan.show') }}" class="text-sm text-gray-500 hover:underline">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Add') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
