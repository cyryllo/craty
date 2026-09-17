<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            @include('settings._back-link')
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Modules') }}</h2>
        </div>
    </x-slot>

    <div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('settings.modules.update') }}" class="bg-white rounded-lg shadow p-6 space-y-5 dark:bg-gray-800">
            @csrf

            @foreach (\App\Support\Modules::MODULES as $key => $module)
                <div class="flex items-start gap-2 {{ !$loop->first ? 'pt-4 border-t border-gray-100 dark:border-gray-700' : '' }}">
                    <input type="hidden" name="module_{{ $key }}_enabled" value="0">
                    <input type="checkbox" id="module_{{ $key }}_enabled" name="module_{{ $key }}_enabled" value="1"
                           @checked(old("module_{$key}_enabled", $setting->{"module_{$key}_enabled"})) class="mt-1 rounded border-gray-300 dark:border-gray-600">
                    <label for="module_{{ $key }}_enabled" class="text-sm text-gray-700 dark:text-gray-300">
                        {{ __($module['label']) }}
                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __($module['description']) }}</span>
                    </label>
                </div>
            @endforeach

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('settings.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
