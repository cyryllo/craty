<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Language') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Choose the language used just for your account. Leave on server default to follow the language an administrator set for everyone.') }}
        </p>
    </header>

    <form method="post" action="{{ route('locale.update') }}" class="mt-6 space-y-6">
        @csrf

        <div class="max-w-xs">
            <x-input-label for="locale" :value="__('Language')" />
            <select id="locale" name="locale" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600">
                <option value="" @selected(! $user->locale)>— {{ __('follow server default') }} —</option>
                @foreach (\App\Models\AppSetting::LOCALES as $code => $label)
                    <option value="{{ $code }}" @selected($user->locale === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'locale-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
