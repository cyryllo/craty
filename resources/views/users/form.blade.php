<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $user->exists ? 'Edytuj konto' : 'Nowe konto' }}</h2>
    </x-slot>

    <div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="bg-white rounded-lg shadow p-6 space-y-4">
            @csrf
            @if ($user->exists) @method('PUT') @endif

            <div>
                <x-input-label for="name" value="Imię i nazwisko" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name', $user->name) }}" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="email" value="E-mail" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email', $user->email) }}" required />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="role" value="Rola" />
                <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300" required>
                    <option value="admin" @selected(old('role', $user->role) == 'admin')>Administrator — pełny dostęp</option>
                    <option value="magazynier" @selected(old('role', $user->role ?: 'magazynier') == 'magazynier')>Magazynier — dodaje i edytuje przedmioty</option>
                    <option value="podglad" @selected(old('role', $user->role) == 'podglad')>Podgląd — tylko odczyt</option>
                </select>
            </div>
            @if ($user->exists)
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="active" name="active" value="1" @checked(old('active', $user->active)) class="rounded border-gray-300">
                    <x-input-label for="active" value="Konto aktywne" class="!mb-0" />
                </div>
            @endif
            <div>
                <x-input-label for="password" :value="$user->exists ? 'Nowe hasło (opcjonalnie)' : 'Hasło'" />
                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" @if (! $user->exists) required @endif />
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:underline">Anuluj</a>
                <x-primary-button>Zapisz</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
