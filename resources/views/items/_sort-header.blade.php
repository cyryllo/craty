{{-- Nagłówek kolumny listy przedmiotów, który sortuje po kliknięciu (ItemController::sortUrl). --}}
<a href="{{ \App\Http\Controllers\ItemController::sortUrl($column, $sort, $dir) }}"
   @class(['inline-flex items-center gap-1 hover:text-gray-900 dark:hover:text-gray-100', 'text-gray-900 dark:text-gray-100' => $sort === $column])>
    {{ $label }}
    @if ($sort === $column)
        <span aria-hidden="true">{{ $dir === 'asc' ? '▲' : '▼' }}</span>
    @endif
</a>
