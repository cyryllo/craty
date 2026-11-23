{{-- Podkategoria z jej podkategoriami (rekurencyjnie). $seen chroni przed pętlą w starych danych. --}}
@php($subs = isset($seen[$category->id]) ? collect() : $children->get($category->id, collect()))
@php($seen[$category->id] = true)
<div class="border border-gray-100 rounded-md dark:border-gray-700" x-data="{ open: true }" @structure-toggle.window="open = $event.detail">
    <div class="flex items-center justify-between gap-2 px-3 py-2 bg-gray-50 rounded-md dark:bg-gray-900">
        @if ($subs->isNotEmpty())
            <button type="button" @click="open = ! open" :aria-expanded="open.toString()" class="flex items-center gap-2 min-w-0 text-sm font-semibold text-gray-900 dark:text-gray-100">
                <svg class="w-3.5 h-3.5 shrink-0 transition-transform" :class="open ? 'rotate-90' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                <span class="truncate">{{ $category->name }}</span>
                <span class="font-mono text-xs font-medium text-gray-500 dark:text-gray-400">{{ $category->code }}</span>
                <span x-show="! open" x-cloak class="text-xs font-normal text-gray-500 dark:text-gray-400">· {{ trans_choice(':count subcategory|:count subcategories', $subs->count(), ['count' => $subs->count()]) }}</span>
            </button>
        @else
            <span class="flex items-center gap-2 min-w-0 ps-5 text-sm font-semibold text-gray-900 dark:text-gray-100">
                <span class="truncate">{{ $category->name }}</span>
                <span class="font-mono text-xs font-medium text-gray-500 dark:text-gray-400">{{ $category->code }}</span>
            </span>
        @endif
        <div class="flex items-center gap-1 shrink-0">
            @include('categories._count', ['category' => $category, 'total' => $totals[$category->id] ?? $category->items_count, 'hasChildren' => $subs->isNotEmpty()])
            <a href="{{ route('categories.create', ['parent_id' => $category->id]) }}" class="p-1.5 rounded text-gray-400 hover:bg-white hover:text-gray-800 dark:hover:bg-gray-800 dark:hover:text-white" title="{{ __('Add a subcategory') }}" aria-label="{{ __('Add a subcategory') }}">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            </a>
            <a href="{{ route('categories.edit', $category) }}" class="p-1.5 rounded text-gray-400 hover:bg-white hover:text-gray-800 dark:hover:bg-gray-800 dark:hover:text-white" title="{{ __('Edit category') }}" aria-label="{{ __('Edit category') }}">@include('warehouse-structure._pencil')</a>
        </div>
    </div>
    @if ($subs->isNotEmpty())
        <div x-show="open" class="ps-6 pe-2 py-2 space-y-2">
            @foreach ($subs as $sub)
                @include('categories._node', ['category' => $sub, 'depth' => $depth + 1, 'seen' => $seen])
            @endforeach
        </div>
    @endif
</div>
