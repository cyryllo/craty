<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Mail') }}</h2>
    </x-slot>

    <div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

        @if (session('status'))
            <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-md p-4 dark:bg-green-950 dark:border-green-800 dark:text-green-300">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-md p-4 dark:bg-red-950 dark:border-red-800 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-sky-50 border border-sky-200 text-sky-800 text-sm rounded-md p-4 dark:bg-sky-950 dark:text-sky-300 dark:border-sky-800">
            {{ __('Leave these fields empty to keep using the server\'s own mail configuration (today: :mailer).', ['mailer' => config('mail.default')]) }}
        </div>

        <form method="POST" action="{{ route('settings.mail.update') }}" class="bg-white rounded-lg shadow p-6 space-y-4 dark:bg-gray-800">
            @csrf

            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                    <x-input-label for="mail_host" :value="__('SMTP host')" />
                    <x-text-input id="mail_host" name="mail_host" class="mt-1 block w-full" value="{{ old('mail_host', $setting->mail_host) }}" placeholder="smtp.example.com" />
                    <x-input-error :messages="$errors->get('mail_host')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="mail_port" :value="__('Port')" />
                    <x-text-input id="mail_port" name="mail_port" type="number" class="mt-1 block w-full" value="{{ old('mail_port', $setting->mail_port ?? 587) }}" />
                    <x-input-error :messages="$errors->get('mail_port')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="mail_encryption" :value="__('Encryption')" />
                <select id="mail_encryption" name="mail_encryption" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600">
                    @foreach (\App\Models\AppSetting::MAIL_ENCRYPTIONS as $value => $label)
                        <option value="{{ $value }}" @selected(old('mail_encryption', $setting->mail_encryption ?? 'tls') === $value)>{{ __($label) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label for="mail_username" :value="__('Username')" />
                <x-text-input id="mail_username" name="mail_username" class="mt-1 block w-full" value="{{ old('mail_username', $setting->mail_username) }}" autocomplete="off" />
                <x-input-error :messages="$errors->get('mail_username')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="mail_password" :value="__('Password')" />
                <x-text-input id="mail_password" name="mail_password" type="password" class="mt-1 block w-full" placeholder="{{ $setting->mail_password ? '••••••••' : '' }}" autocomplete="new-password" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Leave empty to keep the current password.') }}</p>
                <x-input-error :messages="$errors->get('mail_password')" class="mt-1" />
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="mail_from_address" :value="__('From address')" />
                    <x-text-input id="mail_from_address" name="mail_from_address" type="email" class="mt-1 block w-full" value="{{ old('mail_from_address', $setting->mail_from_address ?? config('mail.from.address')) }}" />
                    <x-input-error :messages="$errors->get('mail_from_address')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="mail_from_name" :value="__('From name')" />
                    <x-text-input id="mail_from_name" name="mail_from_name" class="mt-1 block w-full" value="{{ old('mail_from_name', $setting->mail_from_name ?? config('mail.from.name')) }}" />
                    <x-input-error :messages="$errors->get('mail_from_name')" class="mt-1" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('settings.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>

        <div class="bg-white rounded-lg shadow p-6 space-y-3 dark:bg-gray-800">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Send a test email') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Sends using whatever is currently filled in above, without saving it first.') }}</p>
            <form method="POST" action="{{ route('settings.mail.test') }}" class="flex flex-wrap gap-2">
                @csrf
                {{-- Kopiujemy pola z formularza wyżej, żeby przetestować to, co jest wpisane, a nie tylko to, co już zapisane. --}}
                <input type="hidden" name="mail_host" value="{{ old('mail_host', $setting->mail_host) }}">
                <input type="hidden" name="mail_port" value="{{ old('mail_port', $setting->mail_port ?? 587) }}">
                <input type="hidden" name="mail_encryption" value="{{ old('mail_encryption', $setting->mail_encryption ?? 'tls') }}">
                <input type="hidden" name="mail_username" value="{{ old('mail_username', $setting->mail_username) }}">
                <input type="hidden" name="mail_from_address" value="{{ old('mail_from_address', $setting->mail_from_address ?? config('mail.from.address')) }}">
                <input type="hidden" name="mail_from_name" value="{{ old('mail_from_name', $setting->mail_from_name ?? config('mail.from.name')) }}">
                <input type="email" name="test_email" required placeholder="{{ __('Send to this address') }}" value="{{ old('test_email', auth()->user()->email) }}" class="flex-1 min-w-[200px] rounded-md border-gray-300 text-sm dark:border-gray-600">
                <x-secondary-button type="submit">{{ __('Send test email') }}</x-secondary-button>
            </form>
            <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('Uses the currently saved password unless you also typed a new one above.') }}</p>
        </div>
    </div>
</x-app-layout>
