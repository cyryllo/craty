<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ $result ? __('Import result') : __('Import preview') }}</h2>
    </x-slot>

    @php
        $fieldLabels = [
            'name' => __('Item name'), 'category_id' => __('Category'), 'storage_location_id' => __('Location'),
            'condition' => __('Condition'), 'status' => __('Status'), 'value' => __('Value'),
            'serial_number' => __('Serial number'), 'ean' => 'EAN', 'purchased_at' => __('Purchase date'),
            'warranty_until' => __('Warranty until'), 'inspection_interval_months' => __('Inspection interval (months)'),
            'next_inspection_at' => __('Next inspection'), 'stationary' => __('Stationary'), 'description' => __('Description'),
        ];
        $badge = [
            'create' => ['bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200', __('New')],
            'update' => ['bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200', __('Change')],
            'unchanged' => ['bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', __('No change')],
            'error' => ['bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200', __('Error')],
        ];
    @endphp

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        @if ($analysis)
            @php $counts = $analysis['counts']; @endphp
            <div class="bg-white rounded-lg shadow p-6 space-y-4 dark:bg-gray-800">
                <p class="text-sm">
                    <span class="text-emerald-700 font-medium dark:text-emerald-400">{{ __('New items: :count', ['count' => $counts['create']]) }}</span>
                    · <span class="text-indigo-700 font-medium dark:text-indigo-400">{{ __('Changed: :count', ['count' => $counts['update']]) }}</span>
                    · <span class="text-gray-600 dark:text-gray-400">{{ __('Unchanged: :count', ['count' => $counts['unchanged']]) }}</span>
                    · <span class="{{ $counts['error'] ? 'text-red-700 font-medium dark:text-red-400' : 'text-gray-600 dark:text-gray-400' }}">{{ __('Errors: :count', ['count' => $counts['error']]) }}</span>
                </p>
                @if ($counts['error'])
                    <p class="text-sm text-amber-800 dark:text-amber-300">{{ __('Rows with errors will be skipped. You can confirm the rest now, or fix the file and check it again.') }}</p>
                @endif
                <div class="flex flex-wrap gap-3">
                    @if ($analysis['plan'])
                        <form method="POST" action="{{ route('data-transfer.items.apply') }}">
                            @csrf
                            <x-primary-button>{{ __('Confirm import') }} ({{ count($analysis['plan']) }})</x-primary-button>
                        </form>
                    @endif
                    <a href="{{ route('data-transfer.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">{{ __('Cancel') }}</a>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-x-auto dark:bg-gray-800">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100 dark:text-gray-400 dark:border-gray-700">
                            <th class="py-2 px-4">{{ __('Line') }}</th>
                            <th class="py-2 pr-4"></th>
                            <th class="py-2 pr-4">{{ __('Item') }}</th>
                            <th class="py-2 pr-4">{{ __('Details') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($analysis['rows'] as $row)
                            <tr class="align-top">
                                <td class="py-2 px-4 text-gray-500 dark:text-gray-400">{{ $row['line'] }}</td>
                                <td class="py-2 pr-4"><span class="inline-block px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap {{ $badge[$row['action']][0] }}">{{ $badge[$row['action']][1] }}</span></td>
                                <td class="py-2 pr-4 text-gray-900 dark:text-gray-100">
                                    {{ $row['name'] ?: '—' }}
                                    @if ($row['inventory_no'])
                                        <div class="text-xs text-gray-500 font-mono dark:text-gray-400">{{ $row['inventory_no'] }}</div>
                                    @endif
                                </td>
                                <td class="py-2 pr-4">
                                    @foreach ($row['changes'] as $field => [$old, $new])
                                        <div class="text-gray-700 dark:text-gray-300">
                                            <span class="text-gray-500 dark:text-gray-400">{{ $fieldLabels[$field] ?? $field }}:</span>
                                            <span class="line-through text-gray-400">{{ $old === '' ? '∅' : $old }}</span> → <span class="font-medium">{{ $new === '' ? '∅' : $new }}</span>
                                        </div>
                                    @endforeach
                                    @foreach ($row['messages'] as $message)
                                        <div class="{{ $row['action'] === 'error' ? 'text-red-700 dark:text-red-400' : 'text-amber-700 dark:text-amber-400' }}">{{ $message }}</div>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-white rounded-lg shadow p-6 space-y-4 dark:bg-gray-800">
                <p class="text-sm">
                    <span class="text-emerald-700 font-medium dark:text-emerald-400">{{ __('Added: :count', ['count' => $result['created']]) }}</span>
                    · <span class="text-indigo-700 font-medium dark:text-indigo-400">{{ __('Changed: :count', ['count' => $result['updated']]) }}</span>
                </p>
                @if ($result['rows'])
                    <ul class="text-sm divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($result['rows'] as $row)
                            <li class="py-1.5 flex gap-3">
                                <span class="inline-block px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap {{ $badge[$row['action']][0] }}">{{ $badge[$row['action']][1] }}</span>
                                <span class="font-mono text-gray-500 dark:text-gray-400">{{ $row['inventory_no'] }}</span>
                                <span class="text-gray-900 dark:text-gray-100">{{ $row['name'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('items.index', ['clear' => 1]) }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">{{ __('Go to items') }}</a>
                    <a href="{{ route('data-transfer.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">{{ __('Import and export') }}</a>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
