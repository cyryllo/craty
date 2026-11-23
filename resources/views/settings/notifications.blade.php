<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            @include('settings._back-link')
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Notifications') }}</h2>
        </div>
    </x-slot>

    <div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('settings.notifications.update') }}" class="bg-white rounded-lg shadow p-6 space-y-5 dark:bg-gray-800">
            @csrf

            <div class="flex items-start gap-2">
                <input type="hidden" name="password_reset_enabled" value="0">
                <input type="checkbox" id="password_reset_enabled" name="password_reset_enabled" value="1"
                       @checked(old('password_reset_enabled', $setting->password_reset_enabled)) class="mt-1 rounded border-gray-300 dark:border-gray-600">
                <label for="password_reset_enabled" class="text-sm text-gray-700 dark:text-gray-300">
                    {{ __('Allow users to reset their own password') }}
                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                        {{ __('When off, the "Forgot your password?" link disappears and the reset pages are unreachable — an administrator has to reset the password manually from Users.') }}
                    </span>
                </label>
            </div>

            @if (\App\Support\Modules::isEnabled('loans'))
            <div class="flex items-start gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                <input type="hidden" name="loan_due_notifications_enabled" value="0">
                <input type="checkbox" id="loan_due_notifications_enabled" name="loan_due_notifications_enabled" value="1"
                       @checked(old('loan_due_notifications_enabled', $setting->loan_due_notifications_enabled)) class="mt-1 rounded border-gray-300 dark:border-gray-600">
                <label for="loan_due_notifications_enabled" class="text-sm text-gray-700 dark:text-gray-300">
                    {{ __('Email a daily summary of overdue loans') }}
                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Sent to every active user (admin/warehouse keeper), not directly to borrowers — some loans are recorded with just a name, with no account/email in the app. Requires the hosting\'s cron to actually run the Laravel scheduler.') }}
                    </span>
                </label>
            </div>

            @endif

            @if (\App\Support\Modules::isEnabled('service'))
                <div class="flex items-start gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <input type="hidden" name="service_reminders_enabled" value="0">
                    <input type="checkbox" id="service_reminders_enabled" name="service_reminders_enabled" value="1"
                           @checked(old('service_reminders_enabled', $setting->service_reminders_enabled)) class="mt-1 rounded border-gray-300 dark:border-gray-600">
                    <label for="service_reminders_enabled" class="text-sm text-gray-700 dark:text-gray-300">
                        {{ __('Email a weekly service reminder') }}
                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Every Monday morning: inspections that are overdue or due within 14 days, warranties ending within 30 days and repairs past their expected return. Sent to every active user, only when there is something on the list. Requires the hosting\'s cron to actually run the Laravel scheduler.') }}
                        </span>
                    </label>
                </div>
            @endif

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('settings.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
