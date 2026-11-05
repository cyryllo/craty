<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Contacts') }}</h2>
            <a href="{{ route('contacts.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 shrink-0">+ {{ __('New contact') }}</a>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-4">
        <form method="GET" action="{{ route('contacts.index') }}" class="flex flex-col sm:flex-row gap-2">
            <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('Search by name, phone or email') }}" aria-label="{{ __('Search') }}" class="flex-1 rounded-md border-gray-300 text-sm dark:border-gray-600">
            <select name="type" aria-label="{{ __('Type') }}" class="rounded-md border-gray-300 text-sm dark:border-gray-600">
                <option value="">{{ __('All types') }}</option>
                @foreach (\App\Models\Contact::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected($type === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
            <button class="px-4 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Search') }}</button>
        </form>

        <div class="bg-white rounded-lg shadow overflow-x-auto dark:bg-gray-800">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase dark:bg-gray-900 dark:text-gray-400">
                    <tr>
                        <th class="text-left px-4 py-3">{{ __('Name or company') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Type') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Phone') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Email') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Loans') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($contacts as $contact)
                        <tr class="hover:bg-gray-50 cursor-pointer dark:hover:bg-gray-700/50" onclick="window.location='{{ route('contacts.show', $contact) }}'">
                            <td class="px-4 py-3">
                                <a href="{{ route('contacts.show', $contact) }}" class="font-medium text-gray-900 hover:underline dark:text-gray-100">{{ $contact->name }}</a>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $contact->typeLabel() }}</td>
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap dark:text-gray-400">{{ $contact->phone }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $contact->email }}</td>
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap dark:text-gray-400">
                                {{ $contact->loans_count }}
                                @if ($contact->active_loans_count)
                                    <span class="ms-1 text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300">{{ __(':count on loan', ['count' => $contact->active_loans_count]) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">
                                {{ $search !== '' || $type ? __('No contacts match the search.') : __('No contacts yet. They are also added when you loan an item to someone new.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $contacts->links() }}
    </div>
</x-app-layout>
