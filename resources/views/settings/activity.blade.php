<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            @include('settings._back-link')
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Activity log') }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                @if ($retentionDays)
                    {{ __('Who did what and when. Entries older than :days days are deleted automatically.', ['days' => $retentionDays]) }}
                @else
                    {{ __('Who did what and when. Entries are kept without a time limit.') }}
                @endif
            </p>
        </div>
    </x-slot>

    @php($field = 'rounded-md border-gray-300 text-sm dark:border-gray-600')
    <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-4">
        {{-- Zawsze: bezpieczeństwo i administracja. Zmiany danych tylko po włączeniu. --}}
        <form method="POST" action="{{ route('settings.activity.update') }}" class="bg-white rounded-lg shadow p-5 flex flex-col sm:flex-row sm:items-center gap-3 dark:bg-gray-800">
            @csrf
            <input type="hidden" name="activity_log_all_enabled" value="0">
            <label class="flex items-start gap-2 text-sm text-gray-700 flex-1 dark:text-gray-300">
                <input type="checkbox" name="activity_log_all_enabled" value="1" @checked($setting->activity_log_all_enabled) class="mt-0.5 rounded border-gray-300 dark:border-gray-600">
                <span>
                    <span class="font-medium">{{ __('Also record data changes') }}</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Sign-ins, accounts, settings and updates are always recorded. With this on, adding, changing and deleting items, categories, places, contacts, loans and other data is recorded too.') }}</span>
                </span>
            </label>
            <label class="text-sm text-gray-700 shrink-0 dark:text-gray-300">
                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Keep entries for') }}</span>
                <select name="activity_log_retention_days" class="mt-0.5 {{ $field }}">
                    @foreach (\App\Models\ActivityLog::RETENTION_OPTIONS as $days => $label)
                        <option value="{{ $days }}" @selected($retentionDays === $days)>{{ __($label) }}</option>
                    @endforeach
                </select>
            </label>
            <button class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 shrink-0">{{ __('Save') }}</button>
        </form>
        <x-input-error :messages="$errors->get('activity_log_retention_days')" />

        <form method="GET" action="{{ route('settings.activity.index') }}" class="flex flex-col sm:flex-row gap-2 sm:items-center">
            <select name="category" aria-label="{{ __('Type') }}" class="{{ $field }}">
                <option value="">{{ __('all events') }}</option>
                <option value="security" @selected($category === 'security')>{{ __('security and administration') }}</option>
                <option value="data" @selected($category === 'data')>{{ __('data changes') }}</option>
            </select>
            <select name="user" aria-label="{{ __('User') }}" class="{{ $field }}">
                <option value="">{{ __('all users') }}</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected($userId === $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('Search: name, e-mail, item, IP') }}" aria-label="{{ __('Search') }}" class="flex-1 {{ $field }}">
            <button class="px-4 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Filter') }}</button>
        </form>

        <div class="bg-white rounded-lg shadow overflow-x-auto dark:bg-gray-800">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase dark:bg-gray-900 dark:text-gray-400">
                    <tr>
                        <th class="text-left px-4 py-3">{{ __('When') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Who') }}</th>
                        <th class="text-left px-4 py-3">{{ __('What') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Details') }}</th>
                        <th class="text-left px-4 py-3">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($entries as $entry)
                        <tr class="align-top">
                            <td class="px-4 py-2 whitespace-nowrap text-gray-500 tabular-nums dark:text-gray-400">{{ $entry->created_at?->format('d.m.Y H:i') }}</td>
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $entry->user_name ?: __('system') }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span @class([
                                    'text-xs font-medium px-2 py-0.5 rounded-full',
                                    'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200' => in_array($entry->event, ['auth.failed', 'auth.lockout', 'update.failed', 'user.deleted'], true),
                                    'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' => $entry->category === 'data',
                                    'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200' => $entry->category === 'security' && ! in_array($entry->event, ['auth.failed', 'auth.lockout', 'update.failed', 'user.deleted'], true),
                                ])>{{ $entry->eventLabel() }}</span>
                            </td>
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                                @if ($entry->subject_label)<span class="font-medium">{{ $entry->subject_label }}</span>@endif
                                @if ($details = $entry->details())<span class="block text-xs text-gray-500 dark:text-gray-400">{{ $details }}</span>@endif
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap font-mono text-xs text-gray-500 dark:text-gray-400">{{ $entry->ip }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">{{ __('Nothing recorded yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $entries->links() }}
    </div>
</x-app-layout>
