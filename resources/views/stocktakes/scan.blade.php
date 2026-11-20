<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            <a href="{{ route('stocktakes.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">← {{ __('Stocktaking') }}</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Stocktake: :place', ['place' => $stocktake->label]) }}</h2>
        </div>
    </x-slot>

    @vite(['resources/js/scan.js'])

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 grid md:grid-cols-2 gap-6 items-start">
        @include('scan._camera', [
            'mode' => 'batch',
            'hint' => __('Scan every item you find in this place. Each one is saved right away, so you can stop and continue later, also on another device.'),
            'recordUrl' => route('stocktakes.scan', $stocktake),
            'storageKey' => 'craty-stocktake-'.$stocktake->id,
        ])

        <div class="space-y-4">
            <div class="bg-white rounded-lg shadow p-5 text-sm space-y-1 dark:bg-gray-800">
                <p class="text-gray-800 dark:text-gray-200">{{ __('Expected in this place: :count', ['count' => $expectedCount]) }}</p>
                <p class="text-gray-500 dark:text-gray-400">{{ __('Saved so far: :in in place, :out from elsewhere (refresh to update).', ['in' => $inPlaceCount, 'out' => $elsewhereCount]) }}</p>
            </div>

            <div class="bg-white rounded-lg shadow dark:bg-gray-800">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between dark:border-gray-700">
                    <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Scanned on this device') }} (<span data-batch-count>0</span>)</h3>
                </div>
                <p data-batch-flash class="hidden px-5 py-2 text-sm border-b border-gray-100 dark:border-gray-700 data-[kind=ok]:text-emerald-700 data-[kind=warn]:text-amber-700 data-[kind=error]:text-red-600 dark:data-[kind=ok]:text-emerald-400 dark:data-[kind=warn]:text-amber-400 dark:data-[kind=error]:text-red-400"></p>
                <p data-batch-empty class="px-5 py-4 text-sm text-gray-400 dark:text-gray-500">{{ __('Nothing scanned yet.') }}</p>
                <ul data-batch-list class="px-5 divide-y divide-gray-100 dark:divide-gray-700"></ul>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2">
                <form method="POST" action="{{ route('stocktakes.finish', $stocktake) }}" onsubmit="return confirm('{{ __('Finish the stocktake and show the report?') }}');">
                    @csrf
                    <x-primary-button>{{ __('Finish and show report') }}</x-primary-button>
                </form>
                <form method="POST" action="{{ route('stocktakes.destroy', $stocktake) }}" onsubmit="return confirm('{{ __('Delete this stocktake?') }}');">
                    @csrf @method('DELETE')
                    <button class="text-sm text-red-600 hover:underline dark:text-red-400">{{ __('Delete') }}</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
