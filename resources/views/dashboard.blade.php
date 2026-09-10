<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Panel') }}</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow p-5">
                <p class="text-sm text-gray-500">{{ __('Items in inventory') }}</p>
                <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $totalItems }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-5">
                <p class="text-sm text-gray-500">{{ __('Total value') }}</p>
                <p class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format((float) $totalValue, 0, ',', ' ') }} zł</p>
            </div>
            <div class="bg-white rounded-lg shadow p-5">
                <p class="text-sm text-gray-500">{{ __('On loan') }}</p>
                <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $statusCounts['wypozyczony'] ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-5">
                <p class="text-sm text-gray-500">{{ __('For sale') }}</p>
                <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $statusCounts['do_sprzedazy'] ?? 0 }}</p>
            </div>
        </div>

        @if ($overdueLoans->isNotEmpty())
            <div class="bg-white rounded-lg shadow">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-900">{{ __('Overdue loans') }}</h3>
                </div>
                <ul class="divide-y divide-gray-100">
                    @foreach ($overdueLoans as $loan)
                        <li class="px-5 py-3 flex items-center justify-between text-sm">
                            <a href="{{ route('items.show', $loan->item) }}" class="text-gray-900 hover:underline">{{ $loan->item->name }}</a>
                            <span class="text-gray-500">{{ $loan->borrowerLabel() }} — {{ __('due') }} {{ $loan->due_at->format('d.m.Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-medium text-gray-900">{{ __('Recently added') }}</h3>
                <a href="{{ route('items.index') }}" class="text-sm text-indigo-600 hover:underline">{{ __('view all') }}</a>
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse ($recentItems as $item)
                    <li class="px-5 py-3 flex items-center justify-between text-sm">
                        <div>
                            <a href="{{ route('items.show', $item) }}" class="font-medium text-gray-900 hover:underline">{{ $item->name }}</a>
                            <span class="text-gray-400 font-mono ms-2">{{ $item->inventory_no }}</span>
                        </div>
                        <span class="text-gray-500">{{ $item->storageLocation?->label() ?? '—' }}</span>
                    </li>
                @empty
                    <li class="px-5 py-6 text-sm text-gray-500">{{ __('No items yet') }} — <a href="{{ route('items.create') }}" class="text-indigo-600 hover:underline">{{ __('add the first one') }}</a>.</li>
                @endforelse
            </ul>
        </div>

    </div>
</x-app-layout>
