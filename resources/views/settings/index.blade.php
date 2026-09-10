<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ustawienia</h2>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            @if (auth()->user()->isAdmin())
                <a href="{{ route('users.index') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition">
                    <h3 class="font-medium text-gray-900">Użytkownicy</h3>
                    <p class="text-sm text-gray-500 mt-1">Konta, role, dostęp do panelu.</p>
                </a>

                <a href="{{ route('settings.app.edit') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition">
                    <h3 class="font-medium text-gray-900">Ustawienia aplikacji</h3>
                    <p class="text-sm text-gray-500 mt-1">Nazwa i logo widoczne w panelu.</p>
                </a>
            @endif

            <a href="{{ route('categories.index') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition">
                <h3 class="font-medium text-gray-900">Kategorie</h3>
                <p class="text-sm text-gray-500 mt-1">Drzewo kategorii przedmiotów.</p>
            </a>

            <a href="{{ route('warehouses.index') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition">
                <h3 class="font-medium text-gray-900">Magazyny</h3>
                <p class="text-sm text-gray-500 mt-1">Fizyczne magazyny/hale.</p>
            </a>

            <a href="{{ route('storage-locations.index') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition">
                <h3 class="font-medium text-gray-900">Lokalizacje</h3>
                <p class="text-sm text-gray-500 mt-1">Regały, półki, pojemniki w magazynach.</p>
            </a>

        </div>
    </div>
</x-app-layout>
