{{-- Liczba przedmiotów w samej kategorii (link do listy) i łącznie z podkategoriami. --}}
<a href="{{ route('items.index', ['category_id' => $category->id, 'f' => 1]) }}" class="text-sm font-semibold text-indigo-700 hover:underline whitespace-nowrap dark:text-indigo-300">{{ __(':count items', ['count' => $category->items_count]) }}</a>
@if ($hasChildren && $total !== $category->items_count)
    <span class="text-xs text-gray-500 whitespace-nowrap dark:text-gray-400" title="{{ __('including subcategories') }}">({{ $total }})</span>
@endif
