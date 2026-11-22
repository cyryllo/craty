<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 dark:bg-gray-800 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <x-application-logo class="block h-9 w-9 fill-current text-gray-800 dark:text-gray-200" />
                        <span class="hidden md:block font-semibold text-gray-800 tracking-tight dark:text-gray-200">{{ \App\Models\AppSetting::current()->effectiveName() }}</span>
                    </a>
                </div>

                <!-- Ikony pozycji menu obok loga — tylko mobile, żeby dotrzeć do
                     Panelu/Przedmiotów/Sprzedaży bez otwierania rozwijanego
                     menu z hamburgera. Widoczne poniżej xl (1280 px): przy
                     wszystkich modułach włączonych siedem napisów nie mieści
                     się w węższym oknie i nachodziło na ikony po prawej. Ten sam zestaw co "Navigation Links"
                     niżej (pełne linki tekstowe na desktopie) i co pierwsza
                     sekcja rozwijanego menu mobilnego niżej — trzy miejsca
                     celowo, nie pętla, bo mają zupełnie różny markup. -->
                <div class="flex items-center gap-1 ms-2 xl:hidden">
                    @unless (auth()->user()->isMember())
                        <a href="{{ route('dashboard') }}" title="{{ __('Panel') }}"
                           @class(['flex items-center justify-center w-8 sm:w-9 h-9 rounded-md', 'text-indigo-600 dark:text-indigo-400' => request()->routeIs('dashboard'), 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300' => ! request()->routeIs('dashboard')])>
                            <x-icon name="panel" class="w-5 h-5" />
                        </a>
                        <a href="{{ route('items.index') }}" title="{{ __('Items') }}"
                           @class(['flex items-center justify-center w-8 sm:w-9 h-9 rounded-md', 'text-indigo-600 dark:text-indigo-400' => request()->routeIs('items.*'), 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300' => ! request()->routeIs('items.*')])>
                            <x-icon name="items" class="w-5 h-5" />
                        </a>
                    @endunless
                    @if (auth()->user()->isMagazynier())
                        <a href="{{ route('loans.index') }}" title="{{ __('Loans') }}"
                           @class(['hidden sm:flex items-center justify-center w-8 sm:w-9 h-9 rounded-md', 'text-indigo-600 dark:text-indigo-400' => request()->routeIs('loans.*', 'loan-groups.*'), 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300' => ! request()->routeIs('loans.*', 'loan-groups.*')])>
                            <x-icon name="loans" class="w-5 h-5" />
                        </a>
                    @endif
                    @if (\App\Support\Modules::isEnabled('bookings'))
                        <a href="{{ route('bookings.index') }}" title="{{ __('Machines') }}"
                           @class(['items-center justify-center w-8 sm:w-9 h-9 rounded-md', 'flex' => auth()->user()->isMember(), 'hidden sm:flex' => ! auth()->user()->isMember(), 'text-indigo-600 dark:text-indigo-400' => request()->routeIs('bookings.*'), 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300' => ! request()->routeIs('bookings.*')])>
                            <x-icon name="bookings" class="w-5 h-5" />
                        </a>
                    @endif
                    @if (auth()->user()->isMagazynier() && \App\Support\Modules::isEnabled('sales'))
                        <a href="{{ route('sale-listings.index') }}" title="{{ __('Sale') }}"
                           @class(['flex items-center justify-center w-8 sm:w-9 h-9 rounded-md', 'text-indigo-600 dark:text-indigo-400' => request()->routeIs('sale-listings.*'), 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300' => ! request()->routeIs('sale-listings.*')])>
                            <x-icon name="sale" class="w-5 h-5" />
                        </a>
                    @endif
                    @if (auth()->user()->isMagazynier() && \App\Support\Modules::isEnabled('contacts'))
                        <a href="{{ route('contacts.index') }}" title="{{ __('Contacts') }}"
                           @class(['flex items-center justify-center w-8 sm:w-9 h-9 rounded-md', 'text-indigo-600 dark:text-indigo-400' => request()->routeIs('contacts.*'), 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300' => ! request()->routeIs('contacts.*')])>
                            <x-icon name="contacts" class="w-5 h-5" />
                        </a>
                    @endif
                    @if (auth()->user()->isMagazynier() && \App\Support\Modules::isEnabled('consumables'))
                        <a href="{{ route('consumables.index') }}" title="{{ __('Consumables') }}"
                           @class(['hidden sm:flex items-center justify-center w-8 sm:w-9 h-9 rounded-md', 'text-indigo-600 dark:text-indigo-400' => request()->routeIs('consumables.*'), 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300' => ! request()->routeIs('consumables.*')])>
                            <x-icon name="consumables" class="w-5 h-5" />
                        </a>
                    @endif
                    @if (auth()->user()->isMagazynier() && \App\Support\Modules::isEnabled('service'))
                        <a href="{{ route('service.index') }}" title="{{ __('Service') }}"
                           @class(['flex items-center justify-center w-8 sm:w-9 h-9 rounded-md', 'text-indigo-600 dark:text-indigo-400' => request()->routeIs('service.*'), 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300' => ! request()->routeIs('service.*')])>
                            <x-icon name="service" class="w-5 h-5" />
                        </a>
                    @endif
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 xl:-my-px xl:ms-8 xl:flex">
                    @unless (auth()->user()->isMember())
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            {{ __('Panel') }}
                        </x-nav-link>
                        <x-nav-link :href="route('items.index')" :active="request()->routeIs('items.*')">
                            {{ __('Items') }}
                        </x-nav-link>
                    @endunless
                    @if (auth()->user()->isMagazynier())
                        <x-nav-link :href="route('loans.index')" :active="request()->routeIs('loans.*', 'loan-groups.*')">
                            {{ __('Loans') }}
                        </x-nav-link>
                    @endif
                    @if (\App\Support\Modules::isEnabled('bookings'))
                        <x-nav-link :href="route('bookings.index')" :active="request()->routeIs('bookings.*')">
                            {{ __('Machines') }}
                        </x-nav-link>
                    @endif
                    @if (auth()->user()->isMagazynier() && \App\Support\Modules::isEnabled('sales'))
                        <x-nav-link :href="route('sale-listings.index')" :active="request()->routeIs('sale-listings.*')">
                            {{ __('Sale') }}
                        </x-nav-link>
                    @endif
                    @if (auth()->user()->isMagazynier() && \App\Support\Modules::isEnabled('contacts'))
                        <x-nav-link :href="route('contacts.index')" :active="request()->routeIs('contacts.*')">
                            {{ __('Contacts') }}
                        </x-nav-link>
                    @endif
                    @if (auth()->user()->isMagazynier() && \App\Support\Modules::isEnabled('consumables'))
                        <x-nav-link :href="route('consumables.index')" :active="request()->routeIs('consumables.*')">
                            {{ __('Consumables') }}
                        </x-nav-link>
                    @endif
                    @if (auth()->user()->isMagazynier() && \App\Support\Modules::isEnabled('service'))
                        <x-nav-link :href="route('service.index')" :active="request()->routeIs('service.*')">
                            {{ __('Service') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden xl:flex xl:items-center xl:ms-4 gap-1">
                @include('layouts._marketplace-link')
                @include('layouts._theme-toggle')

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 dark:bg-gray-800 dark:text-gray-400">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        @if (auth()->user()->isMagazynier())
                            <x-dropdown-link :href="route('settings.index')">
                                <span class="inline-flex items-center gap-2">
                                    <x-icon name="settings" class="w-4 h-4" />
                                    {{ __('Settings') }}
                                </span>
                            </x-dropdown-link>
                        @endif
                        <x-dropdown-link :href="route('profile.edit')">
                            <span class="inline-flex items-center gap-2">
                                <x-icon name="profile" class="w-4 h-4" />
                                {{ __('Profile') }}
                            </span>
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                <span class="inline-flex items-center gap-2">
                                    <x-icon name="logout" class="w-4 h-4" />
                                    {{ __('Log Out') }}
                                </span>
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center gap-1 xl:hidden">
                @include('layouts._marketplace-link')
                @include('layouts._theme-toggle')

                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out dark:text-gray-500 dark:hover:bg-gray-700">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden xl:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @unless (auth()->user()->isMember())
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    <span class="inline-flex items-center gap-2">
                        <x-icon name="panel" class="w-4 h-4" />
                        {{ __('Panel') }}
                    </span>
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('items.index')" :active="request()->routeIs('items.*')">
                    <span class="inline-flex items-center gap-2">
                        <x-icon name="items" class="w-4 h-4" />
                        {{ __('Items') }}
                    </span>
                </x-responsive-nav-link>
            @endunless
            @if (auth()->user()->isMagazynier())
                <x-responsive-nav-link :href="route('loans.index')" :active="request()->routeIs('loans.*', 'loan-groups.*')">
                    <span class="inline-flex items-center gap-2">
                        <x-icon name="loans" class="w-4 h-4" />
                        {{ __('Loans') }}
                    </span>
                </x-responsive-nav-link>
            @endif
            @if (\App\Support\Modules::isEnabled('bookings'))
                <x-responsive-nav-link :href="route('bookings.index')" :active="request()->routeIs('bookings.*')">
                    <span class="inline-flex items-center gap-2">
                        <x-icon name="bookings" class="w-4 h-4" />
                        {{ __('Machines') }}
                    </span>
                </x-responsive-nav-link>
            @endif
            @if (auth()->user()->isMagazynier() && \App\Support\Modules::isEnabled('sales'))
                <x-responsive-nav-link :href="route('sale-listings.index')" :active="request()->routeIs('sale-listings.*')">
                    <span class="inline-flex items-center gap-2">
                        <x-icon name="sale" class="w-4 h-4" />
                        {{ __('Sale') }}
                    </span>
                </x-responsive-nav-link>
            @endif
            @if (auth()->user()->isMagazynier() && \App\Support\Modules::isEnabled('contacts'))
                <x-responsive-nav-link :href="route('contacts.index')" :active="request()->routeIs('contacts.*')">
                    <span class="inline-flex items-center gap-2">
                        <x-icon name="contacts" class="w-4 h-4" />
                        {{ __('Contacts') }}
                    </span>
                </x-responsive-nav-link>
            @endif
            @if (auth()->user()->isMagazynier() && \App\Support\Modules::isEnabled('consumables'))
                <x-responsive-nav-link :href="route('consumables.index')" :active="request()->routeIs('consumables.*')">
                    <span class="inline-flex items-center gap-2">
                        <x-icon name="consumables" class="w-4 h-4" />
                        {{ __('Consumables') }}
                    </span>
                </x-responsive-nav-link>
            @endif
            @if (auth()->user()->isMagazynier() && \App\Support\Modules::isEnabled('service'))
                <x-responsive-nav-link :href="route('service.index')" :active="request()->routeIs('service.*')">
                    <span class="inline-flex items-center gap-2">
                        <x-icon name="service" class="w-4 h-4" />
                        {{ __('Service') }}
                    </span>
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-700">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500 dark:text-gray-400">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                @if (auth()->user()->isMagazynier())
                    <x-responsive-nav-link :href="route('settings.index')" :active="request()->routeIs('settings.*', 'categories.*', 'warehouses.*', 'warehouse-structure.*', 'rooms.*', 'storage-locations.*', 'users.*')">
                        <span class="inline-flex items-center gap-2">
                            <x-icon name="settings" class="w-4 h-4" />
                            {{ __('Settings') }}
                        </span>
                    </x-responsive-nav-link>
                @endif
                <x-responsive-nav-link :href="route('profile.edit')">
                    <span class="inline-flex items-center gap-2">
                        <x-icon name="profile" class="w-4 h-4" />
                        {{ __('Profile') }}
                    </span>
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        <span class="inline-flex items-center gap-2">
                            <x-icon name="logout" class="w-4 h-4" />
                            {{ __('Log Out') }}
                        </span>
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
