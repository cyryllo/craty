<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Scan') }}</h2>
            <a href="{{ route('scan.batch') }}" class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Scan a series') }} →</a>
        </div>
    </x-slot>

    @vite(['resources/js/scan.js'])

    <div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8">
        @include('scan._camera', ['mode' => 'single', 'hint' => __('Point the camera at an item\'s QR label or its manufacturer barcode.')])
    </div>
</x-app-layout>
