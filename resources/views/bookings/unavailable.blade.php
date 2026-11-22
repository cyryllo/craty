<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Machine booking') }}</h2>
    </x-slot>

    {{-- Rola Użytkownik widzi tylko rezerwacje maszyn; gdy moduł jest wyłączony, zamiast 404 ta informacja. --}}
    <div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow p-6 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">
            {{ __('Machine booking is switched off at the moment. Ask the workshop staff if you need a machine.') }}
        </div>
    </div>
</x-app-layout>
