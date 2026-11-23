<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ $category->exists ? __('Edit category') : __('New category') }}</h2>
    </x-slot>

    <div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}" class="bg-white rounded-lg shadow p-6 space-y-4 dark:bg-gray-800">
            @csrf
            @if ($category->exists) @method('PUT') @endif

            <div>
                <x-input-label for="name" :value="__('Category name')" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name', $category->name) }}" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="code" :value="__('Code (used in the inventory number, e.g. NAR)')" />
                <x-text-input id="code" name="code" class="mt-1 block w-full uppercase" maxlength="8" value="{{ old('code', $category->code) }}" required />
                <x-input-error :messages="$errors->get('code')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="parent_id" :value="__('Parent category')" />
                <select id="parent_id" name="parent_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600">
                    <option value="">— {{ __('none (main category)') }} —</option>
                    {{-- W kolejności drzewa, z wcięciem; bez tej kategorii i jej podkategorii (pętla). --}}
                    @foreach ($options as $option)
                        <option value="{{ $option['category']->id }}" @selected(old('parent_id', $category->parent_id) == $option['category']->id)>{!! str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $option['depth']) !!}{{ $option['depth'] ? '└ ' : '' }}{{ $option['category']->name }} ({{ $option['category']->code }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('parent_id')" class="mt-1" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('categories.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>

        {{-- Usuwanie na dole edycji, jak w Strukturze magazynu. --}}
        @if ($category->exists)
            <div class="mt-6 bg-white rounded-lg shadow p-6 dark:bg-gray-800">
                @if ($category->items_count || $category->children_count)
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('This category can be deleted once it has no items and no subcategories (now: :items items, :subs subcategories).', ['items' => $category->items_count, 'subs' => $category->children_count]) }}</p>
                @else
                    <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm(@js(__('Delete this category?')));">
                        @csrf @method('DELETE')
                        <button class="text-sm font-medium text-red-600 hover:underline dark:text-red-400">{{ __('Delete category') }}</button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
