<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $warehouse->exists ? __('Edit warehouse') : __('New warehouse') }}</h2>
    </x-slot>

    <div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $warehouse->exists ? route('warehouses.update', $warehouse) : route('warehouses.store') }}" class="bg-white rounded-lg shadow p-6 space-y-4">
            @csrf
            @if ($warehouse->exists) @method('PUT') @endif

            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name', $warehouse->name) }}" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="code" :value="__('Code (used in the location code, e.g. M1)')" />
                <x-text-input id="code" name="code" class="mt-1 block w-full uppercase" maxlength="8" value="{{ old('code', $warehouse->code) }}" required />
                <x-input-error :messages="$errors->get('code')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="address" :value="__('Address / description')" />
                <x-text-input id="address" name="address" class="mt-1 block w-full" value="{{ old('address', $warehouse->address) }}" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <a href="{{ route('warehouses.index') }}" class="text-sm text-gray-500 hover:underline">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
