<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Loans') }}</h2>
            <a href="{{ route('scan.batch') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                <x-icon name="scan" class="w-4 h-4" /> {{ __('Loan out a kit') }}
            </a>
        </div>
    </x-slot>

    @php($badge = 'text-xs font-medium px-2 py-0.5 rounded-full whitespace-nowrap')
    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        {{-- Wypożyczone teraz: pojedynczo --}}
        <div class="bg-white rounded-lg shadow dark:bg-gray-800">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between dark:border-gray-700">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('On loan now') }}</h3>
                <span class="text-sm text-gray-400 dark:text-gray-500">{{ $singleLoans->count() }}</span>
            </div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($singleLoans as $loan)
                    <li class="px-5 py-3 flex items-start justify-between gap-3 text-sm">
                        <div class="min-w-0">
                            <a href="{{ route('items.show', $loan->item) }}" class="text-gray-900 hover:underline dark:text-gray-100">{{ $loan->item->name }}</a>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $loan->borrowerLabel() }} · {{ __('since') }} {{ $loan->borrowed_at->format('d.m.Y') }}</span>
                        </div>
                        @if ($loan->due_at)
                            <span @class([$badge, 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' => $loan->isOverdue(), 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => ! $loan->isOverdue()])>{{ __('until') }} {{ $loan->due_at->format('d.m.Y') }}</span>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-4 text-sm text-gray-400 dark:text-gray-500">{{ __('Nothing is on loan.') }}</li>
                @endforelse
            </ul>
        </div>

        {{-- Zestawy --}}
        <div class="bg-white rounded-lg shadow dark:bg-gray-800">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between dark:border-gray-700">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Kits on loan') }}</h3>
                <span class="text-sm text-gray-400 dark:text-gray-500">{{ $groups->count() }}</span>
            </div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($groups as ['group' => $group, 'loans' => $loans])
                    <li class="px-5 py-3 flex items-start justify-between gap-3 text-sm">
                        <div class="min-w-0">
                            <a href="{{ route('loan-groups.show', $group) }}" class="text-gray-900 hover:underline dark:text-gray-100">{{ $group->protocolNumber() }} · {{ $group->borrowerLabel() }}</a>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ trans_choice(':count item still out|:count items still out', $loans->count()) }}: {{ $loans->pluck('item.name')->take(4)->implode(', ') }}@if ($loans->count() > 4)…@endif</span>
                        </div>
                        @if ($group->due_at)
                            <span @class([$badge, 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' => $group->due_at->isPast() && ! $group->due_at->isToday(), 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => ! ($group->due_at->isPast() && ! $group->due_at->isToday())])>{{ __('until') }} {{ $group->due_at->format('d.m.Y') }}</span>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-4 text-sm text-gray-400 dark:text-gray-500">{{ __('No kits on loan.') }}</li>
                @endforelse
            </ul>
        </div>

        {{-- Rezerwacje --}}
        <div class="bg-white rounded-lg shadow dark:bg-gray-800">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between dark:border-gray-700">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Reservations') }}</h3>
                <span class="text-sm text-gray-400 dark:text-gray-500">{{ $reservations->count() }}</span>
            </div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($reservations as $reservation)
                    <li class="px-5 py-3 flex items-start justify-between gap-3 text-sm">
                        <div class="min-w-0">
                            <a href="{{ route('items.show', $reservation->item) }}" class="text-gray-900 hover:underline dark:text-gray-100">{{ $reservation->item->name }}</a>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $reservation->borrowerLabel() }}</span>
                        </div>
                        <span @class([$badge, 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300' => $reservation->isCurrent(), 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => ! $reservation->isCurrent()])>{{ $reservation->starts_on->format('d.m') }}–{{ $reservation->ends_on->format('d.m.Y') }}</span>
                    </li>
                @empty
                    <li class="px-5 py-4 text-sm text-gray-400 dark:text-gray-500">{{ __('No upcoming reservations. Reserve an item from its page.') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>
