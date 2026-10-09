{{-- Regały → półki → pojemniki jednego pomieszczenia (albo regałów bez pomieszczenia: $roomId = null).
     Węzeł bez własnej lokalizacji (np. jest R3-P2-K1, ale nie sam "R3") to tylko grupa — bez ołówka. --}}
@php($branch = array_filter(['warehouse_id' => $warehouseId, 'room_id' => $roomId ?? 'none']))
@foreach ($racks as $rack)
    <div class="border border-gray-100 rounded-md dark:border-gray-700"
         x-data="{ open: true }" @structure-toggle.window="open = $event.detail">
        <div class="flex items-center justify-between gap-2 px-3 py-2 bg-gray-50 rounded-md dark:bg-gray-900">
            <button type="button" @click="open = ! open" :aria-expanded="open.toString()"
                    class="flex items-center gap-2 min-w-0 text-sm font-semibold text-gray-900 dark:text-gray-100">
                <svg class="w-3.5 h-3.5 shrink-0 transition-transform" :class="open ? 'rotate-90' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                <span class="truncate">{{ $rack['rack'] !== '' ? __('Rack').' '.$rack['rack'] : __('No rack') }}</span>
                @if ($rack['rack'] !== '')<span class="font-mono text-xs font-medium text-gray-500 dark:text-gray-400">R{{ $rack['rack'] }}</span>@endif
                <span x-show="! open" x-cloak class="text-xs font-normal text-gray-500 dark:text-gray-400">· {{ trans_choice(':count shelf|:count shelves', $rack['shelves']->count(), ['count' => $rack['shelves']->count()]) }}</span>
            </button>
            <div class="flex items-center gap-1 shrink-0">
                <span class="relative group">
                    <a href="{{ route('items.index', $branch + ['rack' => $rack['rack']]) }}" class="text-xs text-indigo-700 hover:underline dark:text-indigo-300">{{ __(':count items', ['count' => $rack['count']]) }}</a>
                    @include('warehouse-structure._names-tooltip', ['names' => $rack['names'], 'note' => $rack['location']?->note, 'align' => 'right'])
                </span>
                @if ($rack['location'])
                    <a href="{{ route('storage-locations.edit', $rack['location']) }}" class="p-1.5 rounded text-gray-400 hover:bg-white hover:text-gray-800 dark:hover:bg-gray-800 dark:hover:text-white" title="{{ __('Edit location') }}" aria-label="{{ __('Edit location') }}">@include('warehouse-structure._pencil')</a>
                @endif
            </div>
        </div>

        <div x-show="open" class="pl-8 pr-3 py-3 space-y-3">
            @foreach ($rack['shelves'] as $shelf)
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between gap-2 text-sm">
                        <span class="text-gray-800 dark:text-gray-200">
                            {{ $shelf['shelf'] !== '' ? __('Shelf').' '.$shelf['shelf'] : __('No shelf') }}
                            @if ($shelf['location']?->note)<span class="text-gray-500 dark:text-gray-400">— {{ $shelf['location']->note }}</span>@endif
                        </span>
                        <span class="flex items-center gap-1 shrink-0">
                            <span class="relative group">
                                <a href="{{ route('items.index', $branch + ['rack' => $rack['rack'], 'shelf' => $shelf['shelf']]) }}" class="text-xs text-indigo-700 hover:underline dark:text-indigo-300">{{ __(':count items', ['count' => $shelf['count']]) }}</a>
                                @include('warehouse-structure._names-tooltip', ['names' => $shelf['names'], 'note' => $shelf['location']?->note, 'align' => 'right'])
                            </span>
                            @if ($shelf['location'])
                                <a href="{{ route('storage-locations.edit', $shelf['location']) }}" class="p-1 rounded text-gray-400 hover:bg-gray-100 hover:text-gray-800 dark:hover:bg-gray-700 dark:hover:text-white" title="{{ __('Edit location') }}" aria-label="{{ __('Edit location') }}">@include('warehouse-structure._pencil')</a>
                            @endif
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($shelf['bins'] as $bin)
                            <span class="relative group inline-flex items-center rounded-full bg-gray-100 border border-gray-200 text-sm dark:bg-gray-900 dark:border-gray-700">
                                @include('warehouse-structure._names-tooltip', ['names' => $names->get($bin->id, collect()), 'note' => $bin->note])
                                <a href="{{ route('items.index', ['storage_location_id' => $bin->id]) }}" class="pl-3 pr-1.5 py-1 text-gray-800 hover:text-indigo-700 dark:text-gray-200 dark:hover:text-indigo-300">K{{ $bin->bin }} <span class="text-xs text-gray-500 dark:text-gray-400">· {{ $bin->items_count }}</span></a>
                                <a href="{{ route('storage-locations.edit', $bin) }}" class="pr-2 pl-0.5 py-1 text-gray-400 hover:text-gray-800 dark:hover:text-white" title="{{ __('Edit location') }}" aria-label="{{ __('Edit location') }}">@include('warehouse-structure._pencil', ['size' => 'w-3 h-3'])</a>
                            </span>
                        @endforeach
                        @if ($shelf['shelf'] !== '')
                            <a href="{{ route('storage-locations.create', $branch + ['rack' => $rack['rack'], 'shelf' => $shelf['shelf']]) }}" class="structure-add">+ {{ __('Bin') }}</a>
                        @endif
                    </div>
                </div>
            @endforeach
            @if ($rack['rack'] !== '')
                <a href="{{ route('storage-locations.create', $branch + ['rack' => $rack['rack']]) }}" class="structure-add">+ {{ __('Shelf') }}</a>
            @endif
        </div>
    </div>
@endforeach
