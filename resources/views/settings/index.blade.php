<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ustawienia</h2>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            @if (auth()->user()->isAdmin())
                <a href="{{ route('users.index') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition flex gap-4 items-start">
                    <x-icon name="users" class="w-8 h-8 text-indigo-500 shrink-0" />
                    <div>
                        <h3 class="font-medium text-gray-900">Użytkownicy</h3>
                        <p class="text-sm text-gray-500 mt-1">Konta, role, dostęp do panelu.</p>
                    </div>
                </a>

                <a href="{{ route('settings.app.edit') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition flex gap-4 items-start">
                    <x-icon name="branding" class="w-8 h-8 text-indigo-500 shrink-0" />
                    <div>
                        <h3 class="font-medium text-gray-900">Ustawienia aplikacji</h3>
                        <p class="text-sm text-gray-500 mt-1">Nazwa i logo widoczne w panelu.</p>
                    </div>
                </a>
            @endif

            <a href="{{ route('categories.index') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition flex gap-4 items-start">
                <x-icon name="categories" class="w-8 h-8 text-indigo-500 shrink-0" />
                <div>
                    <h3 class="font-medium text-gray-900">Kategorie</h3>
                    <p class="text-sm text-gray-500 mt-1">Drzewo kategorii przedmiotów.</p>
                </div>
            </a>

            <a href="{{ route('warehouses.index') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition flex gap-4 items-start">
                <x-icon name="warehouse" class="w-8 h-8 text-indigo-500 shrink-0" />
                <div>
                    <h3 class="font-medium text-gray-900">Magazyny</h3>
                    <p class="text-sm text-gray-500 mt-1">Fizyczne magazyny/hale.</p>
                </div>
            </a>

            <a href="{{ route('storage-locations.index') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition flex gap-4 items-start">
                <x-icon name="location" class="w-8 h-8 text-indigo-500 shrink-0" />
                <div>
                    <h3 class="font-medium text-gray-900">Lokalizacje</h3>
                    <p class="text-sm text-gray-500 mt-1">Regały, półki, pojemniki w magazynach.</p>
                </div>
            </a>

        </div>
    </div>
</x-app-layout>
