<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Updates') }}</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

        @if (session('status'))
            <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-md p-4 dark:bg-green-950 dark:border-green-800 dark:text-green-300">
                {{ session('status') }}
            </div>
        @endif

        @if (session('updateError'))
            <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-md p-4 whitespace-pre-line dark:bg-red-950 dark:border-red-800 dark:text-red-300">
                {{ session('updateError') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">{{ __('Current version') }}</h3>
            <p class="text-2xl font-semibold text-gray-900 mt-1 dark:text-gray-100">{{ $currentVersion }}</p>
        </div>

        <form method="POST" action="{{ route('settings.updates.upload') }}" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6 space-y-4 dark:bg-gray-800">
            @csrf
            <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Upload an update package') }}</h3>

            @unless ($uploadLimitSufficient)
                <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-md p-4 dark:bg-red-950 dark:border-red-800 dark:text-red-300">
                    {{ __('This server currently only accepts uploads up to :limit (upload_max_filesize/post_max_size in php.ini). Update packages are typically 25-30 MB — ask your hosting provider to raise these limits before uploading, or the upload may silently fail.', [
                        'limit' => number_format($maxUploadBytes / 1048576, 1).' MB',
                    ]) }}
                </div>
            @endunless

            <div>
                <x-input-label for="package" :value="__('Update file (.zip)')" />
                <input id="package" name="package" type="file" accept=".zip" required class="mt-1 block w-full text-sm">
                <x-input-error :messages="$errors->get('package')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="checksum" :value="__('Expected SHA-256 checksum (optional)')" />
                <x-text-input id="checksum" name="checksum" class="mt-1 block w-full font-mono text-xs" placeholder="a1b2c3…" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('If you paste the checksum from the release notes, the upload is verified against it before anything is touched.') }}</p>
                <x-input-error :messages="$errors->get('checksum')" class="mt-1" />
            </div>

            <div class="bg-amber-50 border border-amber-200 text-amber-900 text-sm rounded-md p-4 dark:bg-amber-950 dark:border-amber-800 dark:text-amber-300">
                {{ __('This overwrites the application code. A copy of the current code is taken automatically first, and can be restored below if something goes wrong. This does not undo database migrations — restore the automatic backup taken just before the update if the new version added any.') }}
            </div>

            <div class="flex justify-end">
                <x-primary-button>{{ __('Apply update') }}</x-primary-button>
            </div>
        </form>

        <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
            <h3 class="font-medium text-gray-900 mb-2 dark:text-gray-100">{{ __('Roll back') }}</h3>
            @if ($canRollback)
                <p class="text-sm text-gray-600 mb-4 dark:text-gray-400">
                    {{ __('Restores the code from before the last update (version :version, applied :date). This only restores code, not the database.', ['version' => $state['from_version'], 'date' => \Illuminate\Support\Carbon::parse($state['applied_at'])->format('Y-m-d H:i')]) }}
                </p>
                <form method="POST" action="{{ route('settings.updates.rollback') }}" onsubmit="return confirm('{{ __('Roll back to the previous code version?') }}');">
                    @csrf
                    <x-secondary-button type="submit">{{ __('Roll back last update') }}</x-secondary-button>
                </form>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No update to roll back yet.') }}</p>
            @endif
        </div>
    </div>
</x-app-layout>
