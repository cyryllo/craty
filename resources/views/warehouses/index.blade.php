<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Magazyny</h2>
            <a href="{{ route('warehouses.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">+ Nowy magazyn</a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">Nazwa</th>
                        <th class="text-left px-4 py-3">Kod</th>
                        <th class="text-left px-4 py-3">Adres</th>
                        <th class="text-left px-4 py-3">Lokalizacji</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($warehouses as $warehouse)
                        <tr>
                            <td class="px-4 py-3 text-gray-900">{{ $warehouse->name }}</td>
                            <td class="px-4 py-3 font-mono text-gray-500">{{ $warehouse->code }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $warehouse->address ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $warehouse->storage_locations_count }}</td>
                            <td class="px-4 py-3 text-right space-x-3">
                                <a href="{{ route('warehouses.edit', $warehouse) }}" class="text-indigo-600 hover:underline">edytuj</a>
                                <form method="POST" action="{{ route('warehouses.destroy', $warehouse) }}" class="inline" onsubmit="return confirm('Usunąć magazyn?');">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline">usuń</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
