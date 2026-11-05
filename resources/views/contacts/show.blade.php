<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <a href="{{ route('contacts.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">← {{ __('Contacts') }}</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ $contact->name }}</h2>
            </div>
            <a href="{{ route('contacts.edit', $contact) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 shrink-0 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Edit') }}</a>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 grid lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-5 space-y-3 text-sm h-fit dark:bg-gray-800">
            <p><span class="text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">{{ $contact->typeLabel() }}</span></p>
            @if ($contact->phone)
                <p><span class="text-gray-500 dark:text-gray-400">{{ __('Phone') }}:</span> <a href="tel:{{ $contact->phone }}" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ $contact->phone }}</a></p>
            @endif
            @if ($contact->email)
                <p><span class="text-gray-500 dark:text-gray-400">{{ __('Email') }}:</span> <a href="mailto:{{ $contact->email }}" class="text-indigo-600 hover:underline break-all dark:text-indigo-400">{{ $contact->email }}</a></p>
            @endif
            @if ($contact->address)
                <p class="whitespace-pre-line text-gray-800 dark:text-gray-200"><span class="text-gray-500 dark:text-gray-400">{{ __('Address') }}:</span><br>{{ $contact->address }}</p>
            @endif
            @if ($contact->notes)
                <p class="whitespace-pre-line text-gray-600 dark:text-gray-400 pt-3 border-t border-gray-100 dark:border-gray-700">{{ $contact->notes }}</p>
            @endif
            @if (! $contact->phone && ! $contact->email && ! $contact->address && ! $contact->notes)
                <p class="text-gray-400 dark:text-gray-500">{{ __('No contact details yet.') }}</p>
            @endif
        </div>

        <div class="lg:col-span-2 bg-white rounded-lg shadow overflow-x-auto dark:bg-gray-800">
            <h3 class="px-5 pt-5 pb-3 text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Loan history') }}</h3>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase dark:bg-gray-900 dark:text-gray-400">
                    <tr>
                        <th class="text-left px-4 py-2">{{ __('Item') }}</th>
                        <th class="text-left px-4 py-2">{{ __('Loaned') }}</th>
                        <th class="text-left px-4 py-2">{{ __('Returned') }}</th>
                        <th class="text-left px-4 py-2">{{ __('Protocols') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($loans as $loan)
                        <tr>
                            <td class="px-4 py-2">
                                @if ($loan->item)
                                    <a href="{{ route('items.show', $loan->item) }}" class="text-gray-900 hover:underline dark:text-gray-100">{{ $loan->item->name }}</a>
                                    <span class="block text-xs font-mono text-gray-400 dark:text-gray-500">{{ $loan->item->inventory_no }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-gray-500 whitespace-nowrap dark:text-gray-400">{{ $loan->borrowed_at->format('d.m.Y') }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                @if ($loan->returned_at)
                                    <span class="text-gray-500 dark:text-gray-400">{{ $loan->returned_at->format('d.m.Y') }}</span>
                                @elseif ($loan->isOverdue())
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300">{{ __('overdue') }} ({{ $loan->due_at->format('d.m.Y') }})</span>
                                @else
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300">{{ __('on loan') }}@if ($loan->due_at) {{ __('until') }} {{ $loan->due_at->format('d.m.Y') }}@endif</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap space-x-3">
                                <a href="{{ route('loans.protocol.issue', $loan) }}" target="_blank" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ __('issue') }}</a>
                                @if ($loan->returned_at)
                                    <a href="{{ route('loans.protocol.return', $loan) }}" target="_blank" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ __('return') }}</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400 dark:text-gray-500">{{ __('No loans yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
