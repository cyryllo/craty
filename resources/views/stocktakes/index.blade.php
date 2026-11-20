<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            <a href="{{ route('warehouse-structure.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">← {{ __('Warehouse structure') }}</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Stocktaking') }}</h2>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <form method="POST" action="{{ route('stocktakes.store') }}" class="bg-white rounded-lg shadow p-5 space-y-3 dark:bg-gray-800">
            @csrf
            <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('New stocktake') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Choose the place to count. A warehouse, room, rack or shelf includes everything below it. Then scan what is actually there; the report shows what is missing and what is lying in the wrong place.') }}</p>
            <div class="flex flex-col sm:flex-row gap-2">
                <select name="location_id" required aria-label="{{ __('Place') }}" class="flex-1 rounded-md border-gray-300 text-sm dark:border-gray-600">
                    <option value="">{{ __('Choose a place…') }}</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->label() }}</option>
                    @endforeach
                </select>
                <x-primary-button>{{ __('Start counting') }}</x-primary-button>
            </div>
            <x-input-error :messages="$errors->get('location_id')" />
        </form>

        <div class="bg-white rounded-lg shadow overflow-x-auto dark:bg-gray-800">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase dark:bg-gray-900 dark:text-gray-400">
                    <tr>
                        <th class="text-left px-4 py-3">{{ __('Place') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Started') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Scanned') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($stocktakes as $stocktake)
                        <tr class="hover:bg-gray-50 cursor-pointer dark:hover:bg-gray-700/50" onclick="window.location='{{ route('stocktakes.show', $stocktake) }}'">
                            <td class="px-4 py-3"><a href="{{ route('stocktakes.show', $stocktake) }}" class="font-medium text-gray-900 hover:underline dark:text-gray-100">{{ $stocktake->label }}</a></td>
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap dark:text-gray-400">{{ $stocktake->created_at->format('d.m.Y H:i') }}@if ($stocktake->starter) · {{ $stocktake->starter->name }}@endif</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $stocktake->scans_count }}</td>
                            <td class="px-4 py-3">
                                @if ($stocktake->isFinished())
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ __('finished') }}</span>
                                @else
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300">{{ __('in progress') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400 dark:text-gray-500">{{ __('No stocktakes yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $stocktakes->links() }}
    </div>
</x-app-layout>
