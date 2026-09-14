<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Backups') }}</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

        @if (session('status'))
            <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-md p-4">
                {{ session('status') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h3 class="font-medium text-gray-900">{{ __('Create a backup now') }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ __('Includes the database and uploaded files.') }}</p>
                </div>
                <form method="POST" action="{{ route('settings.backup.run') }}">
                    @csrf
                    <x-primary-button>{{ __('Create backup') }}</x-primary-button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-medium text-gray-900 mb-4">{{ __('Existing backups') }}</h3>

            @if ($backups->isEmpty())
                <p class="text-sm text-gray-500">{{ __('No backups yet.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b border-gray-100">
                                <th class="py-2 pr-4">{{ __('File') }}</th>
                                <th class="py-2 pr-4">{{ __('Size') }}</th>
                                <th class="py-2 pr-4">{{ __('Date') }}</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($backups as $backup)
                                <tr class="border-b border-gray-50">
                                    <td class="py-2 pr-4 font-mono text-xs text-gray-700 break-all">{{ $backup['path'] }}</td>
                                    <td class="py-2 pr-4 text-gray-600 whitespace-nowrap">{{ number_format($backup['size'] / 1048576, 1) }} MB</td>
                                    <td class="py-2 pr-4 text-gray-600 whitespace-nowrap">{{ $backup['last_modified']->format('Y-m-d H:i') }}</td>
                                    <td class="py-2 whitespace-nowrap">
                                        <div class="flex gap-3 justify-end">
                                            <a href="{{ route('settings.backup.download', $backup['path']) }}" class="text-indigo-600 hover:underline">{{ __('Download') }}</a>
                                            <form method="POST" action="{{ route('settings.backup.destroy', $backup['path']) }}" onsubmit="return confirm('{{ __('Delete this backup?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">{{ __('Delete') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('settings.backup.settings') }}" class="bg-white rounded-lg shadow p-6 space-y-4">
            @csrf
            <h3 class="font-medium text-gray-900">{{ __('Backup settings') }}</h3>

            <div>
                <x-input-label for="backup_retention_days" :value="__('Keep full backups from the last N days')" />
                <x-text-input id="backup_retention_days" name="backup_retention_days" type="number" min="1" max="365" class="mt-1 block w-32" value="{{ old('backup_retention_days', $setting->backup_retention_days) }}" />
                <x-input-error :messages="$errors->get('backup_retention_days')" class="mt-1" />
                <p class="mt-1 text-xs text-gray-500">{{ __('Older backups are removed the next time cleanup runs.') }}</p>
            </div>

            <div class="flex items-start gap-2">
                <input type="hidden" name="backup_include_env" value="0">
                <input type="checkbox" id="backup_include_env" name="backup_include_env" value="1" @checked(old('backup_include_env', $setting->backup_include_env)) class="mt-1 rounded border-gray-300">
                <label for="backup_include_env" class="text-sm text-gray-700">
                    {{ __('Include the .env file in backups') }}
                    <span class="block text-xs text-gray-500">{{ __('Contains secrets (database password, API keys) — only enable this if you understand the risk.') }}</span>
                </label>
            </div>

            <div class="flex justify-end pt-2 border-t border-gray-100">
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>

        <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-md p-4">
            {{ __('Automatic scheduled backups and cleanup require the Laravel scheduler to be wired to a system cron job (run every minute).') }}
        </div>
    </div>
</x-app-layout>
