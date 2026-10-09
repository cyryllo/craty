<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="space-y-1">
                @include('settings._back-link')
                <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Warehouse structure') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Numbers are items in stock in that place, including everything below it — click one to see the list.') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('structure-toggle', { detail: false }))"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">{{ __('Collapse all') }}</button>
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('structure-toggle', { detail: true }))"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">{{ __('Expand all') }}</button>
                <a href="{{ route('warehouses.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">+ {{ __('New warehouse') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-10">
        @forelse ($tree as $node)
            @php($warehouse = $node['warehouse'])
            <section id="warehouse-{{ $warehouse->id }}" class="space-y-4">
                {{-- Magazyn: szeroki, ciemny nagłówek na całą szerokość. --}}
                <div class="bg-gray-900 text-white rounded-lg px-5 py-4 flex flex-wrap items-center justify-between gap-3 dark:bg-gray-950 dark:ring-1 dark:ring-gray-700">
                    <div class="flex items-center gap-3 min-w-0">
                        <x-icon name="warehouse" class="w-7 h-7 text-indigo-300 shrink-0" />
                        <div class="min-w-0">
                            <div class="flex items-baseline gap-2">
                                <h3 class="text-lg font-semibold truncate">{{ $warehouse->name }}</h3>
                                <span class="font-mono text-sm text-indigo-200">{{ $warehouse->code }}</span>
                            </div>
                            @if ($warehouse->address || $node['base'])
                                <div class="text-sm text-gray-300">
                                    {{ $warehouse->address }}@if ($warehouse->address && $node['base']) · @endif
                                    @if ($node['base'])
                                        <a href="{{ route('items.index', ['storage_location_id' => $node['base']->id]) }}" class="text-indigo-200 hover:underline">{{ __('whole warehouse, no specific spot') }}: {{ __(':count items', ['count' => $node['base']->items_count]) }}</a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('items.index', ['warehouse_id' => $warehouse->id]) }}" class="px-2.5 py-1.5 rounded-md bg-white/10 text-sm font-semibold hover:bg-white/20">{{ __(':count items', ['count' => $node['count']]) }}</a>
                        <a href="{{ route('rooms.create', ['warehouse_id' => $warehouse->id]) }}" class="px-3 py-1.5 rounded-md border border-white/30 text-sm font-medium hover:bg-white/10">+ {{ __('Room') }}</a>
                        <a href="{{ route('warehouses.edit', $warehouse) }}" class="p-2 rounded-md text-gray-300 hover:bg-white/10 hover:text-white" title="{{ __('Edit warehouse') }}" aria-label="{{ __('Edit warehouse') }}">@include('warehouse-structure._pencil')</a>
                    </div>
                </div>

                @if ($node['rooms']->isEmpty() && $node['looseRacks']->isEmpty())
                    <div class="bg-white border border-dashed border-gray-300 rounded-lg p-5 flex flex-wrap items-center justify-between gap-3 dark:bg-gray-800 dark:border-gray-600">
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('This warehouse has no rooms yet — all its items are just "in the warehouse". Add a room to put racks in it.') }}</p>
                        <a href="{{ route('rooms.create', ['warehouse_id' => $warehouse->id]) }}" class="structure-add">+ {{ __('Room') }}</a>
                    </div>
                @else
                    {{-- Pomieszczenia: karty w dwóch kolumnach (na telefonie jedna pod drugą). --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
                        @foreach ($node['rooms'] as $roomNode)
                            @php($room = $roomNode['room'])
                            <article class="bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700">
                                <div class="flex items-center justify-between gap-2 px-4 py-3 rounded-t-lg bg-indigo-50 border-b border-indigo-100 dark:bg-indigo-950 dark:border-indigo-900">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <x-icon name="room" class="w-5 h-5 text-indigo-600 shrink-0 dark:text-indigo-400" />
                                        <h4 class="font-semibold text-gray-900 truncate dark:text-gray-100">{{ $room->name }}</h4>
                                        <span class="font-mono text-xs text-indigo-700 dark:text-indigo-300">{{ $room->code }}</span>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <a href="{{ route('items.index', ['warehouse_id' => $warehouse->id, 'room_id' => $room->id]) }}" class="text-sm font-semibold text-indigo-700 hover:underline dark:text-indigo-300">{{ __(':count items', ['count' => $roomNode['count']]) }}</a>
                                        <a href="{{ route('rooms.edit', $room) }}" class="p-2 rounded-md text-gray-500 hover:bg-white hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white" title="{{ __('Edit room') }}" aria-label="{{ __('Edit room') }}">@include('warehouse-structure._pencil')</a>
                                    </div>
                                </div>
                                <div class="p-4 space-y-3">
                                    @if ($roomNode['base'])
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ __('Whole room, no rack') }} ·
                                            <a href="{{ route('items.index', ['storage_location_id' => $roomNode['base']->id]) }}" class="text-indigo-700 hover:underline dark:text-indigo-300">{{ __(':count items', ['count' => $roomNode['base']->items_count]) }}</a>
                                        </p>
                                    @endif
                                    @include('warehouse-structure._racks', ['racks' => $roomNode['racks'], 'warehouseId' => $warehouse->id, 'roomId' => $room->id])
                                    <a href="{{ route('storage-locations.create', ['warehouse_id' => $warehouse->id, 'room_id' => $room->id]) }}" class="structure-add">+ {{ __('Rack in :place', ['place' => $room->name]) }}</a>
                                </div>
                            </article>
                        @endforeach

                        @if ($node['looseRacks']->isNotEmpty())
                            <article class="bg-white border border-dashed border-gray-300 rounded-lg dark:bg-gray-800 dark:border-gray-600">
                                <div class="flex items-center justify-between gap-2 px-4 py-3 rounded-t-lg bg-gray-50 border-b border-gray-100 dark:bg-gray-900 dark:border-gray-700">
                                    <div class="min-w-0">
                                        <h4 class="font-semibold text-gray-800 dark:text-gray-200">{{ __('No room') }}</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('racks standing directly in the warehouse') }}</p>
                                    </div>
                                    <a href="{{ route('items.index', ['warehouse_id' => $warehouse->id, 'room_id' => 'none']) }}" class="text-sm font-semibold text-indigo-700 hover:underline shrink-0 dark:text-indigo-300">{{ __(':count items', ['count' => $node['looseRacks']->sum('count')]) }}</a>
                                </div>
                                <div class="p-4 space-y-3">
                                    @include('warehouse-structure._racks', ['racks' => $node['looseRacks'], 'warehouseId' => $warehouse->id, 'roomId' => null])
                                    <a href="{{ route('storage-locations.create', ['warehouse_id' => $warehouse->id]) }}" class="structure-add">+ {{ __('Rack without a room') }}</a>
                                </div>
                            </article>
                        @endif
                    </div>
                @endif
            </section>
        @empty
            <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                {{ __('No warehouses yet.') }} <a href="{{ route('warehouses.create') }}" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Add the first warehouse') }}</a>.
            </div>
        @endforelse
    </div>
</x-app-layout>
