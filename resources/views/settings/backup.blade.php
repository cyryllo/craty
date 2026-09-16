<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Backups') }}</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

        @if (session('status'))
            <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-md p-4 dark:bg-green-950 dark:border-green-800 dark:text-green-300">
                {{ session('status') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Create a backup now') }}</h3>
                    <p class="text-sm text-gray-500 mt-1 dark:text-gray-400">{{ __('Includes the database and uploaded files.') }}</p>
                </div>
                <form method="POST" action="{{ route('settings.backup.run') }}">
                    @csrf
                    <x-primary-button>{{ __('Create backup') }}</x-primary-button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
            <h3 class="font-medium text-gray-900 mb-4 dark:text-gray-100">{{ __('Existing backups') }}</h3>

            @if ($backups->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No backups yet.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b border-gray-100 dark:text-gray-400 dark:border-gray-700">
                                <th class="py-2 pr-4">{{ __('File') }}</th>
                                <th class="py-2 pr-4">{{ __('Size') }}</th>
                                <th class="py-2 pr-4">{{ __('Date') }}</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($backups as $backup)
                                <tr class="border-b border-gray-50">
                                    <td class="py-2 pr-4 font-mono text-xs text-gray-700 break-all dark:text-gray-300">{{ $backup['path'] }}</td>
                                    <td class="py-2 pr-4 text-gray-600 whitespace-nowrap dark:text-gray-400">{{ number_format($backup['size'] / 1048576, 1) }} MB</td>
                                    <td class="py-2 pr-4 text-gray-600 whitespace-nowrap dark:text-gray-400">{{ $backup['last_modified']->format('Y-m-d H:i') }}</td>
                                    <td class="py-2 whitespace-nowrap">
                                        <div class="flex gap-3 justify-end">
                                            <a href="{{ route('settings.backup.download', $backup['path']) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Download') }}</a>
                                            <form method="POST" action="{{ route('settings.backup.destroy', $backup['path']) }}" onsubmit="return confirm('{{ __('Delete this backup?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline dark:text-red-400">{{ __('Delete') }}</button>
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

        <form method="POST" action="{{ route('settings.backup.settings') }}" class="bg-white rounded-lg shadow p-6 space-y-4 dark:bg-gray-800">
            @csrf
            <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Backup settings') }}</h3>

            <div>
                <x-input-label for="backup_retention_days" :value="__('Keep full backups from the last N days')" />
                <x-text-input id="backup_retention_days" name="backup_retention_days" type="number" min="1" max="365" class="mt-1 block w-32" value="{{ old('backup_retention_days', $setting->backup_retention_days) }}" />
                <x-input-error :messages="$errors->get('backup_retention_days')" class="mt-1" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Older backups are removed the next time cleanup runs.') }}</p>
            </div>

            <div class="flex items-start gap-2">
                <input type="hidden" name="backup_include_env" value="0">
                <input type="checkbox" id="backup_include_env" name="backup_include_env" value="1" @checked(old('backup_include_env', $setting->backup_include_env)) class="mt-1 rounded border-gray-300 dark:border-gray-600">
                <label for="backup_include_env" class="text-sm text-gray-700 dark:text-gray-300">
                    {{ __('Include the .env file in backups') }}
                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Contains secrets (database password, API keys) — only enable this if you understand the risk.') }}</span>
                </label>
            </div>

            <div class="flex justify-end pt-2 border-t border-gray-100 dark:border-gray-700">
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>

        <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-md p-4 space-y-2 dark:bg-amber-950 dark:border-amber-800 dark:text-amber-300">
            <p>{{ __('Automatic scheduled backups and cleanup need the Laravel scheduler running — add this single line to the server\'s crontab (:command):', ['command' => 'crontab -e']) }}</p>
            <code class="block bg-white border border-amber-200 rounded px-3 py-2 font-mono text-xs overflow-x-auto dark:bg-gray-800 dark:border-amber-800">* * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1</code>
            <p>{{ __('No system cron available (e.g. inside Docker)? Run :command in a background process instead — it checks the schedule in a loop, no cron needed.', ['command' => 'php artisan schedule:work']) }}</p>
        </div>
    </div>
</x-app-layout>
