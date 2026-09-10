<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('App settings') }}</h2>
    </x-slot>

    <div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('settings.app.update') }}" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6 space-y-5">
            @csrf

            <div>
                <x-input-label for="name" :value="__('App name')" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name', $setting->name) }}" placeholder="{{ config('app.name') }}" />
                <p class="mt-1 text-xs text-gray-500">{{ __('Leave empty for the default name ":name".', ['name' => config('app.name')]) }}</p>
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <x-input-label value="{{ __('Logo') }}" />
                <div class="mt-2 flex items-center gap-4">
                    <div class="w-16 h-16 rounded-md border border-gray-200 flex items-center justify-center bg-gray-50 overflow-hidden">
                        <x-application-logo class="w-12 h-12 fill-current text-gray-700 object-contain" />
                    </div>
                    <div class="flex-1">
                        <input id="logo" name="logo" type="file" accept="image/*" class="block w-full text-sm">
                        <x-input-error :messages="$errors->get('logo')" class="mt-1" />
                    </div>
                </div>
                @if ($setting->logo_path)
                    <label class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300">
                        {{ __('Remove the current logo and revert to the default') }}
                    </label>
                @endif
            </div>

            <div>
                <x-input-label for="locale" :value="__('Default language for everyone')" />
                <select id="locale" name="locale" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="">— {{ __('follow server default') }} —</option>
                    @foreach (\App\Models\AppSetting::LOCALES as $code => $label)
                        <option value="{{ $code }}" @selected(old('locale', $setting->locale) === $code)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">{{ __('Used for anyone who has not picked a personal language from the user menu.') }}</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <a href="{{ route('settings.index') }}" class="text-sm text-gray-500 hover:underline">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
