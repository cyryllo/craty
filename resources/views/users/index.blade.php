<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Użytkownicy</h2>
            <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">+ Nowe konto</a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">Imię i nazwisko</th>
                        <th class="text-left px-4 py-3">E-mail</th>
                        <th class="text-left px-4 py-3">Rola</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($users as $user)
                        <tr>
                            <td class="px-4 py-3 text-gray-900">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ ucfirst($user->role) }}</td>
                            <td class="px-4 py-3">
                                <span @class(['text-xs font-medium px-2 py-0.5 rounded-full', 'bg-emerald-100 text-emerald-700' => $user->active, 'bg-gray-100 text-gray-500' => ! $user->active])>
                                    {{ $user->active ? 'aktywne' : 'wyłączone' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-3">
                                <a href="{{ route('users.edit', $user) }}" class="text-indigo-600 hover:underline">edytuj</a>
                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline" onsubmit="return confirm('Usunąć konto?');">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">usuń</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
