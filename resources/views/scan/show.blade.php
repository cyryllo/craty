<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Scan') }}</h2>
    </x-slot>

    @vite(['resources/js/scan.js'])

    <div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div id="scanner"
             data-lookup-url="{{ route('scan.lookup') }}"
             data-quick-add-url="{{ route('scan.quick-add.create') }}"
             data-err-no-camera="{{ __('No camera found on this device.') }}"
             data-err-permission="{{ __('Camera access was denied. Allow it in your browser settings and reload the page.') }}"
             data-err-generic="{{ __('Something went wrong while scanning. Try again.') }}"
             class="bg-white rounded-lg shadow p-5 space-y-4">

            <p class="text-sm text-gray-500">{{ __('Point the camera at an item\'s QR label or its manufacturer barcode.') }}</p>

            <div class="relative bg-gray-900 rounded-lg overflow-hidden aspect-square">
                <video class="w-full h-full object-cover" muted playsinline></video>

                <div data-panel="starting" class="absolute inset-0 flex items-center justify-center text-white text-sm bg-gray-900/70">
                    {{ __('Starting the camera…') }}
                </div>
                <div data-panel="redirecting" class="hidden absolute inset-0 flex items-center justify-center text-white text-sm bg-gray-900/70">
                    {{ __('Code found — opening…') }}
                </div>
            </div>

            <div data-panel="error" class="hidden rounded-md bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700" data-error-text></div>
        </div>
    </div>
</x-app-layout>
