{{-- @include('items._status-badge', ['item' => $item]) --}}
<span @class([
    'shrink-0 text-xs font-medium px-2 py-0.5 rounded-full whitespace-nowrap',
    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300' => $item->status === 'dostepny',
    'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300' => $item->status === 'wypozyczony',
    'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' => $item->status === 'w_naprawie',
    'bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-300' => $item->status === 'do_sprzedazy',
    'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => in_array($item->status, ['sprzedany', 'wycofany']),
])>{{ $item->statusLabel() }}</span>
