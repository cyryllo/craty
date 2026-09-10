<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lokalizacje</h2>
            <a href="{{ route('storage-locations.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">+ Nowa lokalizacja</a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">Kod</th>
                        <th class="text-left px-4 py-3">Magazyn</th>
                        <th class="text-left px-4 py-3">Regał / Półka / Pojemnik</th>
                        <th class="text-left px-4 py-3">Przedmiotów</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($locations as $location)
                        <tr>
                            <td class="px-4 py-3 font-mono text-gray-900">{{ $location->code }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $location->warehouse->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ implode(' / ', array_filter([$location->rack, $location->shelf, $location->bin])) ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $location->items_count }}</td>
                            <td class="px-4 py-3 text-right space-x-3">
                                <a href="{{ route('storage-locations.edit', $location) }}" class="text-indigo-600 hover:underline">edytuj</a>
                                <form method="POST" action="{{ route('storage-locations.destroy', $location) }}" class="inline" onsubmit="return confirm('Usunąć lokalizację?');">
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
