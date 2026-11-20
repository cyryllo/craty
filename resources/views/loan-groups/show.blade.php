<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            <a href="{{ route('loans.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">← {{ __('Loans') }}</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Kit :number', ['number' => $group->protocolNumber()]) }}</h2>
        </div>
    </x-slot>

    @php($open = $group->loans->whereNull('returned_at'))
    @php($select = 'rounded-md border-gray-300 text-sm dark:border-gray-600')
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white rounded-lg shadow p-5 text-sm space-y-1 dark:bg-gray-800">
            <p class="text-gray-800 dark:text-gray-200">{{ __('Loaned to') }}:
                @if ($group->contact && \App\Support\Modules::isEnabled('contacts'))
                    <a href="{{ route('contacts.show', $group->contact) }}" class="font-bold hover:underline">{{ $group->borrowerLabel() }}</a>
                @else
                    <b>{{ $group->borrowerLabel() }}</b>
                @endif
            </p>
            <p class="text-gray-500 dark:text-gray-400">
                {{ __('Issued') }} {{ $group->borrowed_at->format('d.m.Y') }}@if ($group->issuer) ({{ $group->issuer->name }})@endif
                @if ($group->due_at) · {{ __('Due date') }}: {{ $group->due_at->format('d.m.Y') }}@endif
            </p>
            @if ($group->notes)
                <p class="text-gray-600 whitespace-pre-line dark:text-gray-400">{{ $group->notes }}</p>
            @endif
            <p class="pt-2 space-x-4">
                <a href="{{ route('loan-groups.protocol.issue', $group) }}" target="_blank" class="text-indigo-600 hover:underline dark:text-indigo-400">📄 {{ __('Issue protocol (PDF)') }}</a>
                @if ($group->loans->whereNotNull('returned_at')->isNotEmpty())
                    <a href="{{ route('loan-groups.protocol.return', $group) }}" target="_blank" class="text-indigo-600 hover:underline dark:text-indigo-400">📄 {{ __('Return protocol (PDF)') }}</a>
                @endif
            </p>
        </div>

        <form method="POST" action="{{ route('loan-groups.return', $group) }}" class="bg-white rounded-lg shadow overflow-x-auto dark:bg-gray-800">
            @csrf
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase dark:bg-gray-900 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 w-8"></th>
                        <th class="text-left px-4 py-3">{{ __('Item') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Condition when returned') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Notes on return') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($group->loans as $loan)
                        <tr class="align-top">
                            <td class="px-4 py-3">
                                @unless ($loan->returned_at)
                                    <input type="checkbox" name="returned[]" value="{{ $loan->id }}" checked aria-label="{{ __('Return :item', ['item' => $loan->item->name]) }}" class="rounded border-gray-300 dark:border-gray-600">
                                @endunless
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('items.show', $loan->item) }}" class="font-medium text-gray-900 hover:underline dark:text-gray-100">{{ $loan->item->name }}</a>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $loan->item->inventory_no }}</span>
                            </td>
                            @if ($loan->returned_at)
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400" colspan="2">
                                    {{ __('Returned') }} {{ $loan->returned_at->format('d.m.Y') }} · {{ \App\Models\Loan::conditionLabelFor($loan->condition_in) ?? '—' }}
                                    @if ($loan->return_notes)<span class="block text-xs">{{ $loan->return_notes }}</span>@endif
                                </td>
                            @else
                                <td class="px-4 py-3">
                                    <select name="condition[{{ $loan->id }}]" aria-label="{{ __('Condition when returned') }}" class="{{ $select }}">
                                        @foreach (\App\Models\Item::CONDITIONS as $value => $label)
                                            <option value="{{ $value }}" @selected($loan->item->condition === $value)>{{ __($label) }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" name="notes[{{ $loan->id }}]" placeholder="{{ __('optional') }}" aria-label="{{ __('Notes on return') }}" class="w-full {{ $select }}">
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($open->isNotEmpty())
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Untick what hasn\'t come back yet; it can be returned later, here or from the item\'s page.') }}</p>
                    <x-input-error :messages="$errors->get('returned')" />
                    <x-primary-button>{{ __('Register return') }}</x-primary-button>
                </div>
            @endif
        </form>
    </div>
</x-app-layout>
