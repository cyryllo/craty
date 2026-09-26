<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Settings') }}</h2>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">

        <div>
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3 dark:text-gray-400">{{ __('Basic settings') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="{{ route('categories.index') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition flex gap-4 items-start dark:bg-gray-800">
                    <x-icon name="categories" class="w-8 h-8 text-indigo-500 shrink-0 dark:text-indigo-400" />
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Categories') }}</h3>
                        <p class="text-sm text-gray-500 mt-1 dark:text-gray-400">{{ __('Item category tree.') }}</p>
                    </div>
                </a>

                <a href="{{ route('warehouses.index') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition flex gap-4 items-start dark:bg-gray-800">
                    <x-icon name="warehouse" class="w-8 h-8 text-indigo-500 shrink-0 dark:text-indigo-400" />
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Warehouses') }}</h3>
                        <p class="text-sm text-gray-500 mt-1 dark:text-gray-400">{{ __('Physical warehouses/halls.') }}</p>
                    </div>
                </a>

                @if (\App\Support\Modules::isEnabled('locations'))
                    <a href="{{ route('rooms.index') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition flex gap-4 items-start dark:bg-gray-800">
                        <x-icon name="room" class="w-8 h-8 text-indigo-500 shrink-0 dark:text-indigo-400" />
                        <div>
                            <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Rooms') }}</h3>
                            <p class="text-sm text-gray-500 mt-1 dark:text-gray-400">{{ __('Optional rooms/halls within a warehouse.') }}</p>
                        </div>
                    </a>

                    <a href="{{ route('storage-locations.index') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition flex gap-4 items-start dark:bg-gray-800">
                        <x-icon name="location" class="w-8 h-8 text-indigo-500 shrink-0 dark:text-indigo-400" />
                        <div>
                            <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Locations') }}</h3>
                            <p class="text-sm text-gray-500 mt-1 dark:text-gray-400">{{ __('Racks, shelves and bins in the warehouses.') }}</p>
                        </div>
                    </a>
                @endif
            </div>
        </div>

        @if (auth()->user()->isAdmin())
            <div>
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3 dark:text-gray-400">{{ __('Advanced settings') }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="{{ route('settings.app.edit') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition flex gap-4 items-start dark:bg-gray-800">
                        <x-icon name="branding" class="w-8 h-8 text-indigo-500 shrink-0 dark:text-indigo-400" />
                        <div>
                            <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('App settings') }}</h3>
                            <p class="text-sm text-gray-500 mt-1 dark:text-gray-400">{{ __('Name, logo and default language shown in the panel.') }}</p>
                        </div>
                    </a>

                    <a href="{{ route('users.index') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition flex gap-4 items-start dark:bg-gray-800">
                        <x-icon name="users" class="w-8 h-8 text-indigo-500 shrink-0 dark:text-indigo-400" />
                        <div>
                            <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Users') }}</h3>
                            <p class="text-sm text-gray-500 mt-1 dark:text-gray-400">{{ __('Accounts, roles, access to the panel.') }}</p>
                        </div>
                    </a>

                    <a href="{{ route('settings.mail.edit') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition flex gap-4 items-start dark:bg-gray-800">
                        <x-icon name="mail" class="w-8 h-8 text-indigo-500 shrink-0 dark:text-indigo-400" />
                        <div>
                            <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Mail') }}</h3>
                            <p class="text-sm text-gray-500 mt-1 dark:text-gray-400">{{ __('SMTP settings used to send email from the app.') }}</p>
                        </div>
                    </a>

                    <a href="{{ route('settings.notifications.edit') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition flex gap-4 items-start dark:bg-gray-800">
                        <x-icon name="bell" class="w-8 h-8 text-indigo-500 shrink-0 dark:text-indigo-400" />
                        <div>
                            <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Notifications') }}</h3>
                            <p class="text-sm text-gray-500 mt-1 dark:text-gray-400">{{ __('Password reset and overdue-loan email notifications.') }}</p>
                        </div>
                    </a>

                    <a href="{{ route('settings.modules.edit') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition flex gap-4 items-start dark:bg-gray-800">
                        <x-icon name="settings" class="w-8 h-8 text-indigo-500 shrink-0 dark:text-indigo-400" />
                        <div>
                            <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Modules') }}</h3>
                            <p class="text-sm text-gray-500 mt-1 dark:text-gray-400">{{ __('Turn optional parts of the app on or off.') }}</p>
                        </div>
                    </a>

                    <a href="{{ route('settings.updates.index') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition flex gap-4 items-start dark:bg-gray-800">
                        <x-icon name="update" class="w-8 h-8 text-indigo-500 shrink-0 dark:text-indigo-400" />
                        <div>
                            <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Updates') }}</h3>
                            <p class="text-sm text-gray-500 mt-1 dark:text-gray-400">{{ __('Upload an update package for this app.') }}</p>
                        </div>
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
