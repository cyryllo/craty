<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Loan out a kit') }}</h2>
    </x-slot>

    @php($field = 'w-full rounded-md border-gray-300 text-sm dark:border-gray-600')
    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('loan-groups.store') }}" class="space-y-6"
              x-data="{ contact: @js(old('contact_id', $contacts->isEmpty() ? 'new' : '')) }">
            @csrf
            <div class="bg-white rounded-lg shadow dark:bg-gray-800">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Items (:count)', ['count' => $items->count()]) }}</h3>
                </div>
                <x-input-error :messages="$errors->get('items')" class="px-5 pt-3" />
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($items as $item)
                        @php($reason = $item->unavailableReason())
                        <li class="px-5 py-3 flex items-start justify-between gap-3 text-sm">
                            <div>
                                <a href="{{ route('items.show', $item) }}" class="font-medium text-gray-900 hover:underline dark:text-gray-100">{{ $item->name }}</a>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $item->inventory_no }} · {{ $item->conditionLabel() }}</span>
                                @if ($reason)
                                    <span class="block text-xs text-red-600 dark:text-red-400">{{ __('Can\'t be loaned') }}: {{ $reason }}</span>
                                @endif
                            </div>
                            @unless ($reason)
                                <input type="hidden" name="items[]" value="{{ $item->id }}">
                            @endunless
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('No items chosen. Scan them in a row or tick them on the item list.') }}
                            <a href="{{ route('scan.batch') }}" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Scan a series') }}</a>
                        </li>
                    @endforelse
                </ul>
                @if ($items->contains(fn ($item) => $item->unavailableReason()))
                    <p class="px-5 py-3 text-xs text-gray-500 border-t border-gray-100 dark:text-gray-400 dark:border-gray-700">{{ __('Items that can\'t be loaned are left out of the kit.') }}</p>
                @endif
            </div>

            @if ($items->contains(fn ($item) => ! $item->unavailableReason()))
                <div class="bg-white rounded-lg shadow p-5 space-y-3 dark:bg-gray-800">
                    @include('loans._borrower-fields')
                    <label for="due_at" class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Due date (optional)') }}</label>
                    <input type="date" id="due_at" name="due_at" value="{{ old('due_at') }}" class="{{ $field }}">
                    <x-input-error :messages="$errors->get('due_at')" />
                    <textarea name="notes" rows="2" placeholder="{{ __('Notes, printed on the protocol (optional)') }}" aria-label="{{ __('Notes') }}" class="{{ $field }}">{{ old('notes') }}</textarea>
                    <div class="flex justify-end">
                        <x-primary-button>{{ __('Loan out the kit') }}</x-primary-button>
                    </div>
                </div>
            @endif
        </form>
    </div>
</x-app-layout>
