<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            <a href="{{ route('stocktakes.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">← {{ __('Stocktaking') }}</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Stocktake: :place', ['place' => $stocktake->label]) }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Finished :date', ['date' => $stocktake->finished_at->format('d.m.Y H:i')]) }}</p>
        </div>
    </x-slot>

    @php($card = 'bg-white rounded-lg shadow dark:bg-gray-800')
    @php($head = 'px-5 py-4 border-b border-gray-100 flex items-center justify-between dark:border-gray-700')
    @php($location = fn ($item) => $item->storageLocation?->label() ?? __('no location'))
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center text-sm">
            @foreach ([[__('Missing'), $missing->count(), 'text-red-600 dark:text-red-400'], [__('Not in its place'), $misplaced->count(), 'text-amber-600 dark:text-amber-400'], [__('In place'), $matching->count(), 'text-emerald-600 dark:text-emerald-400'], [__('Loaned out or under repair'), $away->count(), 'text-gray-600 dark:text-gray-300']] as [$label, $count, $color])
                <div class="{{ $card }} p-4">
                    <p class="text-2xl font-semibold {{ $color }}">{{ $count }}</p>
                    <p class="text-gray-500 dark:text-gray-400">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        <div class="{{ $card }}">
            <div class="{{ $head }}"><h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Missing') }}</h3></div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($missing as $item)
                    <li class="px-5 py-3 text-sm">
                        <a href="{{ route('items.show', $item) }}" class="text-gray-900 hover:underline dark:text-gray-100">{{ $item->name }}</a>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $item->inventory_no }} · {{ $location($item) }}</span>
                    </li>
                @empty
                    <li class="px-5 py-4 text-sm text-gray-400 dark:text-gray-500">{{ __('Nothing is missing.') }}</li>
                @endforelse
            </ul>
        </div>

        <div class="{{ $card }}">
            <div class="{{ $head }}"><h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Found here, but recorded elsewhere') }}</h3></div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($misplaced as $scan)
                    <li class="px-5 py-3 text-sm flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('items.show', $scan->item) }}" class="text-gray-900 hover:underline dark:text-gray-100">{{ $scan->item->name }}</a>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $scan->item->inventory_no }} · {{ __('recorded in: :place', ['place' => $location($scan->item)]) }}</span>
                        </div>
                        @if ($scan->moved_at)
                            <span class="text-xs text-emerald-600 whitespace-nowrap dark:text-emerald-400">✓ {{ __('moved here') }}</span>
                        @else
                            <form method="POST" action="{{ route('stocktakes.move', [$stocktake, $scan->item]) }}">
                                @csrf
                                <button class="text-xs font-medium text-indigo-600 hover:underline whitespace-nowrap dark:text-indigo-400">{{ __('Move here') }}</button>
                            </form>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-4 text-sm text-gray-400 dark:text-gray-500">{{ __('Everything scanned was in its place.') }}</li>
                @endforelse
            </ul>
        </div>

        @if ($away->isNotEmpty())
            <div class="{{ $card }}">
                <div class="{{ $head }}"><h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Loaned out or under repair') }}</h3></div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($away as $item)
                        <li class="px-5 py-3 text-sm">
                            <a href="{{ route('items.show', $item) }}" class="text-gray-900 hover:underline dark:text-gray-100">{{ $item->name }}</a>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $item->statusLabel() }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <details class="{{ $card }}">
            <summary class="{{ $head }} cursor-pointer"><span class="font-medium text-gray-900 dark:text-gray-100">{{ __('In place') }} ({{ $matching->count() }})</span></summary>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($matching as $scan)
                    <li class="px-5 py-2 text-sm"><a href="{{ route('items.show', $scan->item) }}" class="text-gray-900 hover:underline dark:text-gray-100">{{ $scan->item->name }}</a> <span class="text-xs text-gray-500 dark:text-gray-400">{{ $scan->item->inventory_no }}</span></li>
                @endforeach
            </ul>
        </details>
    </div>
</x-app-layout>
