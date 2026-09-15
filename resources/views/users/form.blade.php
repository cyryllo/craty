<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $user->exists ? __('Edit account') : __('New account') }}</h2>
    </x-slot>

    <div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="bg-white rounded-lg shadow p-6 space-y-4">
            @csrf
            @if ($user->exists) @method('PUT') @endif

            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name', $user->name) }}" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email', $user->email) }}" required />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>
            @if ($user->isProtected())
                <div class="rounded-md bg-indigo-50 border border-indigo-200 px-3 py-2 text-sm text-indigo-800">
                    {{ __('This is the main administrator account — its role and status always stay "Administrator" / "active", so nobody can lose access to the admin panel.') }}
                </div>
                <input type="hidden" name="role" value="admin">
                <input type="hidden" name="active" value="1">
            @else
                <div>
                    <x-input-label for="role" :value="__('Role')" />
                    <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300" required>
                        <option value="admin" @selected(old('role', $user->role) == 'admin')>{{ __('Administrator — full access') }}</option>
                        <option value="magazynier" @selected(old('role', $user->role ?: 'magazynier') == 'magazynier')>{{ __('Warehouse worker — adds and edits items') }}</option>
                        <option value="podglad" @selected(old('role', $user->role) == 'podglad')>{{ __('Viewer — read-only') }}</option>
                    </select>
                </div>
                @if ($user->exists)
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="active" name="active" value="1" @checked(old('active', $user->active)) class="rounded border-gray-300">
                        <x-input-label for="active" :value="__('Account active')" class="!mb-0" />
                    </div>
                @endif
            @endif
            <div>
                <x-input-label for="password" :value="$user->exists ? __('New password (optional)') : __('Password')" />
                @if ($user->exists)
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                @else
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
                @endif
                <p class="mt-1 text-xs text-gray-500">{{ __('At least 8 characters, with an uppercase and lowercase letter and a special character.') }}</p>
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm password')" />
                @if ($user->exists)
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                @else
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
                @endif
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:underline">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
