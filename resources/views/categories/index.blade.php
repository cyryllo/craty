<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Categories') }}</h2>
            <a href="{{ route('categories.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">+ {{ __('New category') }}</a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow overflow-x-auto dark:bg-gray-800">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase dark:bg-gray-900 dark:text-gray-400">
                    <tr>
                        <th class="text-left px-4 py-3">{{ __('Category name') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Code') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Parent category') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Items') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($categories as $category)
                        <tr>
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $category->name }}</td>
                            <td class="px-4 py-3 font-mono text-gray-500 dark:text-gray-400">{{ $category->code }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $category->parent?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $category->items_count }}</td>
                            <td class="px-4 py-3 text-right space-x-3">
                                <a href="{{ route('categories.edit', $category) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ __('edit') }}</a>
                                <form method="POST" action="{{ route('categories.destroy', $category) }}" class="inline" onsubmit="return confirm('{{ __('Delete this category?') }}');">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline dark:text-red-400">{{ __('delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
