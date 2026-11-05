<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Service') }}</h2>
    </x-slot>

    <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Repairs, inspections and warranties are managed on each item\'s page. Warranty dates and inspection intervals are set in the item form.') }}</p>
        @include('service._lists')
    </div>
</x-app-layout>
