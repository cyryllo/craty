<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Consumables') }}</h2>
            <a href="{{ route('consumables.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 shrink-0">+ {{ __('New consumable') }}</a>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-4">
        <form method="GET" action="{{ route('consumables.index') }}" class="flex flex-col sm:flex-row gap-2 sm:items-center">
            <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('Search by name') }}" aria-label="{{ __('Search') }}" class="flex-1 rounded-md border-gray-300 text-sm dark:border-gray-600">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="low" value="1" @checked($lowOnly) onchange="this.form.submit()" class="rounded border-gray-300 dark:border-gray-600">
                {{ __('Running low only') }}@if ($lowCount) <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300">{{ $lowCount }}</span>@endif
            </label>
            <button class="px-4 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Search') }}</button>
        </form>

        <div class="bg-white rounded-lg shadow overflow-x-auto dark:bg-gray-800">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase dark:bg-gray-900 dark:text-gray-400">
                    <tr>
                        <th class="text-left px-4 py-3">{{ __('Name') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Location') }}</th>
                        <th class="text-right px-4 py-3">{{ __('Quantity') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Take') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($consumables as $consumable)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('consumables.show', $consumable) }}" class="font-medium text-gray-900 hover:underline dark:text-gray-100">{{ $consumable->name }}</a>
                                @if ($consumable->category)<span class="block text-xs text-gray-500 dark:text-gray-400">{{ $consumable->category->name }}</span>@endif
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $consumable->storageLocation?->label() }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <span @class(['font-medium', 'text-red-600 dark:text-red-400' => $consumable->isLow(), 'text-gray-900 dark:text-gray-100' => ! $consumable->isLow()])>
                                    {{ \App\Models\Consumable::formatQuantity($consumable->quantity) }} {{ $consumable->unitLabel() }}
                                </span>
                                @if ($consumable->min_quantity !== null)
                                    <span class="block text-xs text-gray-400 dark:text-gray-500">{{ __('min.') }} {{ \App\Models\Consumable::formatQuantity($consumable->min_quantity) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('consumables.adjust', $consumable) }}" class="flex items-center gap-1">
                                    @csrf
                                    <input type="hidden" name="mode" value="take">
                                    <input type="number" name="amount" step="any" min="0" value="1" required aria-label="{{ __('Amount to take') }}" class="w-20 rounded-md border-gray-300 text-sm dark:border-gray-600">
                                    <button class="px-3 py-1.5 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">− {{ __('Take') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">{{ $search !== '' || $lowOnly ? __('Nothing matches.') : __('No consumables yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $consumables->links() }}
    </div>
</x-app-layout>
