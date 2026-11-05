{{-- Moduł Serwis: trzy listy "co wymaga uwagi", wspólne dla strony Serwis
    i skrótu na pulpicie ($limit = ile pozycji pokazać w każdej, null = wszystkie). --}}
@php
    $limit ??= null;
    $badge = 'text-xs font-medium px-2 py-0.5 rounded-full whitespace-nowrap';
    $sections = [
        ['title' => __('Under repair'), 'rows' => $openRepairs, 'empty' => __('Nothing is under repair.')],
        ['title' => __('Inspections due'), 'rows' => $inspectionsDue, 'empty' => __('No inspections due in the next :days days.', ['days' => \App\Models\Item::INSPECTION_WARNING_DAYS])],
        ['title' => __('Warranties ending'), 'rows' => $warrantiesEnding, 'empty' => __('No warranties ending in the next :days days.', ['days' => \App\Models\Item::WARRANTY_WARNING_DAYS])],
    ];
@endphp
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    @foreach ($sections as $index => $section)
        <div class="bg-white rounded-lg shadow dark:bg-gray-800">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between dark:border-gray-700">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ $section['title'] }}</h3>
                <span class="text-sm text-gray-400 dark:text-gray-500">{{ $section['rows']->count() }}</span>
            </div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($limit ? $section['rows']->take($limit) : $section['rows'] as $row)
                    @php($item = $index === 0 ? $row->item : $row)
                    <li class="px-5 py-3 text-sm flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('items.show', $item) }}" class="text-gray-900 hover:underline dark:text-gray-100">{{ $item->name }}</a>
                            @if ($index === 0)
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $row->serviceLabel() }} · {{ __('since') }} {{ $row->sent_at->format('d.m.Y') }}</span>
                            @endif
                        </div>
                        @if ($index === 0)
                            @if ($row->expected_at)
                                <span @class([$badge, 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' => $row->isOverdue(), 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => ! $row->isOverdue()])>{{ __('expected') }} {{ $row->expected_at->format('d.m') }}</span>
                            @endif
                        @elseif ($index === 1)
                            <span @class([$badge, 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' => $row->inspectionState() === 'overdue', 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300' => $row->inspectionState() !== 'overdue'])>{{ $row->next_inspection_at->format('d.m.Y') }}</span>
                        @else
                            <span @class([$badge, 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => $row->warrantyState() === 'expired', 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300' => $row->warrantyState() !== 'expired'])>{{ $row->warrantyState() === 'expired' ? __('ended') : __('until') }} {{ $row->warranty_until->format('d.m.Y') }}</span>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-4 text-sm text-gray-400 dark:text-gray-500">{{ $section['empty'] }}</li>
                @endforelse
            </ul>
        </div>
    @endforeach
</div>
