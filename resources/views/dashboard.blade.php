<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Panel') }}</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">

        @php $showSaleTile = auth()->user()->isMagazynier() && \App\Support\Modules::isEnabled('sales'); @endphp
        <div class="grid grid-cols-2 {{ $showSaleTile ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }} gap-4">
            <a href="{{ route('items.index') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Items') }}</p>
                <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $totalItems }}</p>
            </a>
            <div class="bg-white rounded-lg shadow p-5 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total value') }}</p>
                <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format((float) $totalValue, 0, ',', ' ') }} zł</p>
            </div>
            <a href="{{ route('items.index', ['status' => 'wypozyczony']) }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Loaned out') }}</p>
                <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $statusCounts['wypozyczony'] ?? 0 }}</p>
            </a>
            @if ($showSaleTile)
                <a href="{{ route('sale-listings.exported') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('For sale') }}</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $statusCounts['do_sprzedazy'] ?? 0 }}</p>
                </a>
            @endif
        </div>

        @if ($overdueLoans->isNotEmpty())
            <div class="bg-white rounded-lg shadow dark:bg-gray-800">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Overdue loans') }}</h3>
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($overdueLoans as $loan)
                        <li class="px-5 py-3 flex items-center justify-between text-sm">
                            <a href="{{ route('items.show', $loan->item) }}" class="text-gray-900 hover:underline dark:text-gray-100">{{ $loan->item->name }}</a>
                            <span class="text-gray-500 dark:text-gray-400">{{ $loan->borrowerLabel() }} — {{ __('due') }} {{ $loan->due_at->format('d.m.Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white rounded-lg shadow dark:bg-gray-800">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Recently added') }}</h3>
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($recentItems as $item)
                        <li class="px-5 py-3 text-sm">
                            <a href="{{ route('items.show', $item) }}" class="font-medium text-gray-900 hover:underline dark:text-gray-100">{{ $item->name }}</a>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-gray-500 dark:text-gray-400">{{ __('No items yet') }} — <a href="{{ route('items.create') }}" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ __('add the first one') }}</a>.</li>
                    @endforelse
                </ul>
            </div>

            <div class="bg-white rounded-lg shadow dark:bg-gray-800">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Added from phone') }}</h3>
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($needsCompletionItems as $item)
                        <li class="px-5 py-3 text-sm">
                            <a href="{{ route('items.show', $item) }}" class="font-medium text-gray-900 hover:underline dark:text-gray-100">📱 {{ $item->name }}</a>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-gray-500 dark:text-gray-400">{{ __('Nothing to complete.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>

    </div>
</x-app-layout>
