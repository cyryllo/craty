<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <a href="{{ route('consumables.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">← {{ __('Consumables') }}</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ $consumable->name }}</h2>
            </div>
            <a href="{{ route('consumables.edit', $consumable) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 shrink-0 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Edit') }}</a>
        </div>
    </x-slot>

    @php($qty = fn ($value) => \App\Models\Consumable::formatQuantity($value).' '.$consumable->unitLabel())
    @php($input = 'rounded-md border-gray-300 text-sm dark:border-gray-600')
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 grid lg:grid-cols-3 gap-6">
        <div class="space-y-4">
            <div class="bg-white rounded-lg shadow p-5 text-sm space-y-2 dark:bg-gray-800">
                <p @class(['text-3xl font-semibold', 'text-red-600 dark:text-red-400' => $consumable->isLow(), 'text-gray-900 dark:text-gray-100' => ! $consumable->isLow()])>{{ $qty($consumable->quantity) }}</p>
                @if ($consumable->min_quantity !== null)
                    <p class="text-gray-500 dark:text-gray-400">{{ __('Minimum stock') }}: {{ $qty($consumable->min_quantity) }}</p>
                    @if ($consumable->isLow())
                        <p class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-700 inline-block dark:bg-red-900 dark:text-red-300">{{ __('running low') }}</p>
                    @endif
                @endif
                @if ($consumable->storageLocation)<p class="text-gray-600 dark:text-gray-400">{{ __('Location') }}: {{ $consumable->storageLocation->label() }}</p>@endif
                @if ($consumable->category)<p class="text-gray-600 dark:text-gray-400">{{ __('Category') }}: {{ $consumable->category->name }}</p>@endif
                @if ($consumable->notes)<p class="text-gray-600 whitespace-pre-line pt-2 border-t border-gray-100 dark:text-gray-400 dark:border-gray-700">{{ $consumable->notes }}</p>@endif
            </div>

            @foreach ([['take', __('Take'), '−'], ['add', __('Restock'), '+'], ['set', __('Set counted stock'), '=']] as [$mode, $label, $sign])
                <form method="POST" action="{{ route('consumables.adjust', $consumable) }}" class="bg-white rounded-lg shadow p-4 space-y-2 dark:bg-gray-800">
                    @csrf
                    <input type="hidden" name="mode" value="{{ $mode }}">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $sign }} {{ $label }}</p>
                    <div class="flex gap-2">
                        <input type="number" name="amount" step="any" min="0" required placeholder="{{ $consumable->unitLabel() }}" aria-label="{{ $label }}" class="w-28 {{ $input }}">
                        <input type="text" name="note" placeholder="{{ __('Note (optional)') }}" aria-label="{{ __('Note') }}" class="flex-1 min-w-0 {{ $input }}">
                    </div>
                    <button class="w-full px-3 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">{{ $label }}</button>
                </form>
            @endforeach
            <x-input-error :messages="$errors->get('amount')" />
        </div>

        <div class="lg:col-span-2 bg-white rounded-lg shadow overflow-x-auto h-fit dark:bg-gray-800">
            <h3 class="px-5 pt-5 pb-3 text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('History') }}</h3>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase dark:bg-gray-900 dark:text-gray-400">
                    <tr>
                        <th class="text-left px-4 py-2">{{ __('When') }}</th>
                        <th class="text-right px-4 py-2">{{ __('Change') }}</th>
                        <th class="text-right px-4 py-2">{{ __('After') }}</th>
                        <th class="text-left px-4 py-2">{{ __('Who / note') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($movements as $movement)
                        <tr>
                            <td class="px-4 py-2 text-gray-500 whitespace-nowrap dark:text-gray-400">{{ $movement->created_at->format('d.m.Y H:i') }}</td>
                            <td @class(['px-4 py-2 text-right whitespace-nowrap font-medium', 'text-emerald-600 dark:text-emerald-400' => (float) $movement->change > 0, 'text-red-600 dark:text-red-400' => (float) $movement->change < 0, 'text-gray-500' => (float) $movement->change == 0])>
                                {{ (float) $movement->change > 0 ? '+' : '' }}{{ \App\Models\Consumable::formatQuantity($movement->change) }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-700 whitespace-nowrap dark:text-gray-300">{{ \App\Models\Consumable::formatQuantity($movement->quantity_after) }}</td>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $movement->user?->name }}@if ($movement->note) · {{ $movement->note }}@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400 dark:text-gray-500">{{ __('No changes yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
