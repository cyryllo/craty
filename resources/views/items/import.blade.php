<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Import from CSV') }}</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

        <div class="bg-white rounded-lg shadow p-6 space-y-3 dark:bg-gray-800">
            <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('1. Download the template') }}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Fill it in a spreadsheet app (Excel, Google Sheets, LibreOffice) and save/export it back to CSV — this only accepts CSV, not native .xlsx files.') }}
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Category and location are matched by their short code (e.g. ":example_category", ":example_location") — the same codes shown in their own list pages. Leave a column blank to skip it.', ['example_category' => 'NAR', 'example_location' => 'M1-R3-P2']) }}
            </p>
            <a href="{{ route('items.import.template') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                ⬇ {{ __('Download CSV template') }}
            </a>
        </div>

        <form method="POST" action="{{ route('items.import.store') }}" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6 space-y-4 dark:bg-gray-800">
            @csrf
            <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('2. Upload the filled-in file') }}</h3>

            <div>
                <x-input-label for="file" :value="__('CSV file')" />
                <input id="file" name="file" type="file" accept=".csv,text/csv,text/plain" required class="mt-1 block w-full text-sm">
                <x-input-error :messages="$errors->get('file')" class="mt-1" />
            </div>

            <div class="flex justify-end">
                <x-primary-button>{{ __('Import') }}</x-primary-button>
            </div>
        </form>

        @if ($result)
            <div class="bg-white rounded-lg shadow p-6 space-y-4 dark:bg-gray-800">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Import result') }}</h3>
                <p class="text-sm">
                    <span class="text-green-700 font-medium dark:text-green-400">{{ __(':count created', ['count' => $result['created']]) }}</span>
                    @if (($result['pending'] ?? 0) > 0)
                        · <span class="text-amber-700 font-medium dark:text-amber-400">{{ __(':count pending', ['count' => $result['pending']]) }}</span>
                    @endif
                    @if ($result['failed'] > 0)
                        · <span class="text-red-700 font-medium dark:text-red-400">{{ __(':count failed', ['count' => $result['failed']]) }}</span>
                    @endif
                </p>

                @if (!empty($result['rows']))
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 border-b border-gray-100 dark:text-gray-400 dark:border-gray-700">
                                    <th class="py-2 pr-4">{{ __('Line') }}</th>
                                    <th class="py-2 pr-4">{{ __('Item name') }}</th>
                                    <th class="py-2 pr-4">{{ __('Result') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($result['rows'] as $row)
                                    <tr>
                                        <td class="py-2 pr-4 text-gray-500 dark:text-gray-400">{{ $row['line'] }}</td>
                                        <td class="py-2 pr-4 text-gray-900 dark:text-gray-100">{{ $row['name'] ?: '—' }}</td>
                                        <td class="py-2 pr-4">
                                            @if ($row['outcome'] === 'created')
                                                <span class="text-green-700 dark:text-green-400">✓ {{ $row['inventory_no'] }}</span>
                                            @elseif ($row['outcome'] === 'pending')
                                                <span class="text-amber-700 dark:text-amber-400">⚠ {{ $row['message'] }}</span>
                                            @else
                                                <span class="text-red-700 dark:text-red-400">✗ {{ $row['message'] }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif

        @if ($pendingCount > 0)
            <div class="bg-amber-50 border border-amber-200 text-amber-900 text-sm rounded-md p-4 space-y-3 dark:bg-amber-950 dark:border-amber-800 dark:text-amber-300">
                <p>
                    {{ __(':count pending', ['count' => $pendingCount]) }} —
                    {{ __('these items have a category and/or location code that wasn\'t recognized. You can add them now without it ("unassigned"), or fix the CSV and upload it again.') }}
                </p>
                <form method="POST" action="{{ route('items.import.confirm-unassigned') }}">
                    @csrf
                    <x-secondary-button type="submit">{{ __('Add to unassigned') }}</x-secondary-button>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
