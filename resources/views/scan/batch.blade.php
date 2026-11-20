<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Scan a series') }}</h2>
            <a href="{{ route('scan.show') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">{{ __('Single scan') }}</a>
        </div>
    </x-slot>

    @vite(['resources/js/scan.js'])

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 grid md:grid-cols-2 gap-6 items-start">
        @include('scan._camera', ['mode' => 'batch', 'hint' => __('Scan items one after another; each one is added to the list. You can also type a code or use a handheld barcode reader.')])

        <div class="space-y-4">
            <div class="bg-white rounded-lg shadow dark:bg-gray-800">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between dark:border-gray-700">
                    <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Scanned') }} (<span data-batch-count>0</span>)</h3>
                    <button type="button" data-batch-clear class="text-sm text-gray-500 hover:underline dark:text-gray-400">{{ __('Clear list') }}</button>
                </div>
                <p data-batch-flash class="hidden px-5 py-2 text-sm border-b border-gray-100 dark:border-gray-700 data-[kind=ok]:text-emerald-700 data-[kind=warn]:text-amber-700 data-[kind=error]:text-red-600 dark:data-[kind=ok]:text-emerald-400 dark:data-[kind=warn]:text-amber-400 dark:data-[kind=error]:text-red-400"></p>
                <p data-batch-empty class="px-5 py-4 text-sm text-gray-400 dark:text-gray-500">{{ __('Nothing scanned yet.') }}</p>
                <ul data-batch-list class="px-5 divide-y divide-gray-100 dark:divide-gray-700"></ul>
            </div>

            <div class="flex flex-wrap gap-2">
                @if (auth()->user()->isMagazynier())
                    <form method="GET" action="{{ route('loan-groups.create') }}" data-batch-form>
                        <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 disabled:opacity-40">{{ __('Loan out as a kit') }}</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('items.labels.print') }}" target="_blank" data-batch-form>
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 disabled:opacity-40 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">🏷️ {{ __('Print labels') }}</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
