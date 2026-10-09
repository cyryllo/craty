<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            @include('settings._back-link')
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Sale options') }}</h2>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('settings.shop.update') }}" class="bg-white rounded-lg shadow p-6 space-y-6 dark:bg-gray-800">
            @csrf

            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Everything shown on the public home page (flea market). Texts support simple Markdown: an empty line starts a new paragraph, **bold**, ## heading, - list item, [link](https://…).') }}
                <a href="{{ route('marketplace.index', [], false) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ __('View the public page') }}</a>
            </p>

            <div>
                <x-input-label for="shop_description" :value="__('Sale description')" />
                <textarea id="shop_description" name="shop_description" rows="5" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-600">{{ old('shop_description', $setting->shop_description) }}</textarea>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Shown on the home page in the contact box, above the email and phone. For example who sells, how to pick up, payment.') }}</p>
                <x-input-error :messages="$errors->get('shop_description')" class="mt-1" />
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="public_contact_email" :value="__('Contact email shown on the public page')" />
                    <x-text-input id="public_contact_email" name="public_contact_email" type="email" class="mt-1 block w-full" value="{{ old('public_contact_email', $setting->public_contact_email) }}" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Independent from the SMTP "from" address in Settings → Mail.') }}</p>
                    <x-input-error :messages="$errors->get('public_contact_email')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="public_contact_phone" :value="__('Contact phone shown on the public page (optional)')" />
                    <x-text-input id="public_contact_phone" name="public_contact_phone" type="tel" class="mt-1 block w-full" value="{{ old('public_contact_phone', $setting->public_contact_phone) }}" />
                    <x-input-error :messages="$errors->get('public_contact_phone')" class="mt-1" />
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 space-y-6 dark:border-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Legal information: a link appears in the footer of the public pages only when the text is filled in, and opens it in a window.') }}</p>

                <div>
                    <x-input-label for="privacy_policy" :value="__('Privacy policy')" />
                    <textarea id="privacy_policy" name="privacy_policy" rows="8" class="mt-1 block w-full rounded-md border-gray-300 text-sm font-mono dark:border-gray-600">{{ old('privacy_policy', $setting->privacy_policy) }}</textarea>
                    <x-input-error :messages="$errors->get('privacy_policy')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="terms" :value="__('Terms and conditions')" />
                    <textarea id="terms" name="terms" rows="8" class="mt-1 block w-full rounded-md border-gray-300 text-sm font-mono dark:border-gray-600">{{ old('terms', $setting->terms) }}</textarea>
                    <x-input-error :messages="$errors->get('terms')" class="mt-1" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('settings.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
