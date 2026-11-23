<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="space-y-1">
                @include('settings._back-link')
                <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Categories') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Numbers are items in stock in that category; the total in brackets includes its subcategories.') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('structure-toggle', { detail: false }))"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">{{ __('Collapse all') }}</button>
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('structure-toggle', { detail: true }))"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">{{ __('Expand all') }}</button>
                <a href="{{ route('categories.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">+ {{ __('New category') }}</a>
            </div>
        </div>
    </x-slot>

    {{-- Drzewo jak w Strukturze magazynu: kategorie główne to karty, podkategorie rozwijane w środku. --}}
    <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        @if ($roots->isEmpty())
            <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                {{ __('No categories yet.') }} <a href="{{ route('categories.create') }}" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Add the first category') }}</a>.
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
                @foreach ($roots as $root)
                    @php($subs = $children->get($root->id, collect()))
                    <article class="bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700">
                        <div class="flex items-center justify-between gap-2 px-4 py-3 rounded-t-lg bg-indigo-50 border-b border-indigo-100 dark:bg-indigo-950 dark:border-indigo-900">
                            <div class="flex items-center gap-2 min-w-0">
                                <x-icon name="categories" class="w-5 h-5 text-indigo-600 shrink-0 dark:text-indigo-400" />
                                <h3 class="font-semibold text-gray-900 truncate dark:text-gray-100">{{ $root->name }}</h3>
                                <span class="font-mono text-xs text-indigo-700 dark:text-indigo-300">{{ $root->code }}</span>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                @include('categories._count', ['category' => $root, 'total' => $totals[$root->id] ?? $root->items_count, 'hasChildren' => $subs->isNotEmpty()])
                                <a href="{{ route('categories.edit', $root) }}" class="p-2 rounded-md text-gray-500 hover:bg-white hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white" title="{{ __('Edit category') }}" aria-label="{{ __('Edit category') }}">@include('warehouse-structure._pencil')</a>
                            </div>
                        </div>
                        <div class="p-4 space-y-2">
                            @foreach ($subs as $sub)
                                @include('categories._node', ['category' => $sub, 'depth' => 1, 'seen' => [$root->id => true]])
                            @endforeach
                            <a href="{{ route('categories.create', ['parent_id' => $root->id]) }}" class="structure-add">+ {{ __('Subcategory in :name', ['name' => $root->name]) }}</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
