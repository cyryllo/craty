<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            @include('settings._back-link')
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Backups') }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('A backup is one .zip file with the whole database and all uploaded files (photos, attachments, QR codes, logo). Backups are kept on the server in a folder that cannot be reached from the internet.') }}</p>
        </div>
    </x-slot>

    @php($field = 'rounded-md border-gray-300 text-sm dark:border-gray-600')
    @php($typeLabels = ['manual' => __('manual'), 'auto' => __('automatic'), 'pre-restore' => __('before restoring'), 'uploaded' => __('uploaded')])
    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-4">
        <div class="grid md:grid-cols-2 gap-4 items-start">
            <div class="bg-white rounded-lg shadow p-5 space-y-3 dark:bg-gray-800">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Back up now') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    @if ($latest = $backups[0] ?? null)
                        {{ __('Latest backup: :date.', ['date' => $latest['created']->format('d.m.Y H:i')]) }}
                    @else
                        {{ __('There is no backup yet.') }}
                    @endif
                    {{ __('Make one before every update.') }}
                </p>
                <form method="POST" action="{{ route('settings.backups.store') }}" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').textContent = @js(__('Creating the backup…'));">
                    @csrf
                    <button class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 disabled:opacity-60">{{ __('Create a backup') }}</button>
                </form>
            </div>

            <form method="POST" action="{{ route('settings.backups.settings') }}" class="bg-white rounded-lg shadow p-5 space-y-3 dark:bg-gray-800">
                @csrf
                <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Automatic backups') }}</h3>
                <div class="grid grid-cols-2 gap-3">
                    <label class="text-sm text-gray-700 dark:text-gray-300">
                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Automatic backup') }}</span>
                        <select name="backup_schedule" class="mt-0.5 w-full {{ $field }}">
                            @foreach (\App\Services\BackupService::SCHEDULES as $value => $label)
                                <option value="{{ $value }}" @selected($setting->backup_schedule === $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm text-gray-700 dark:text-gray-300">
                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Keep backups for') }}</span>
                        <select name="backup_retention_days" class="mt-0.5 w-full {{ $field }}">
                            @foreach (\App\Services\BackupService::RETENTION_OPTIONS as $days => $label)
                                <option value="{{ $days }}" @selected((int) $setting->backup_retention_days === $days)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Automatic backups need the hosting\'s scheduler (cron) running the Laravel scheduler; in the Docker version it works on its own. The newest backup is never deleted.') }}</p>
                <button class="px-4 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Save') }}</button>
            </form>
        </div>

        @unless ($passwordConfirmed)
            <div class="bg-white rounded-lg shadow p-5 flex flex-wrap items-center justify-between gap-3 dark:bg-gray-800">
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Downloading, uploading and restoring backups require confirming your password.') }}</p>
                <a href="{{ route('settings.backups.confirm') }}" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">{{ __('Confirm password') }}</a>
            </div>
        @endunless

        <div class="bg-white rounded-lg shadow overflow-x-auto dark:bg-gray-800">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase dark:bg-gray-900 dark:text-gray-400">
                    <tr>
                        <th class="text-left px-4 py-3">{{ __('Created') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Type') }}</th>
                        <th class="text-right px-4 py-3">{{ __('Size') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($backups as $backup)
                        <tr>
                            <td class="px-4 py-3">
                                <span class="text-gray-900 tabular-nums dark:text-gray-100">{{ $backup['created']->format('d.m.Y H:i') }}</span>
                                <span class="block font-mono text-xs text-gray-400 dark:text-gray-500">{{ $backup['name'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $typeLabels[$backup['type']] ?? $backup['type'] }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-gray-600 tabular-nums dark:text-gray-300">{{ number_format($backup['size'] / 1048576, 1, ',', ' ') }} MB</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end items-center gap-3">
                                    @if ($passwordConfirmed)
                                        <a href="{{ route('settings.backups.download', $backup['name']) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Download') }}</a>
                                        <form method="POST" action="{{ route('settings.backups.restore', $backup['name']) }}"
                                              onsubmit="if (! confirm(@js(__('Restore this backup? All current data and files will be replaced by the backup. A backup of the current state is made first.')))) return false; this.querySelector('button').disabled = true; this.querySelector('button').textContent = @js(__('Restoring…'));">
                                            @csrf
                                            <button class="font-medium text-amber-700 hover:underline disabled:opacity-60 dark:text-amber-400">{{ __('Restore') }}</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('settings.backups.destroy', $backup['name']) }}" onsubmit="return confirm(@js(__('Delete this backup?')));">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline dark:text-red-400">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">{{ __('There is no backup yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($passwordConfirmed)
            <form method="POST" action="{{ route('settings.backups.upload') }}" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-5 space-y-3 dark:bg-gray-800">
                @csrf
                <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Upload a backup') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('A backup from your computer or from another Craty installation lands on the list above; restore it from there. Largest file this server accepts: :size MB.', ['size' => number_format($maxUploadBytes / 1048576, 0, ',', ' ')]) }}</p>
                <input type="file" name="backup" accept=".zip" required class="block text-sm">
                <x-input-error :messages="$errors->get('backup')" />
                <button class="px-4 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Upload') }}</button>
            </form>
        @endif
    </div>
</x-app-layout>
