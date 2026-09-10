{{-- @include('items._status-badge', ['item' => $item]) --}}
<span @class([
    'shrink-0 text-xs font-medium px-2 py-0.5 rounded-full whitespace-nowrap',
    'bg-emerald-100 text-emerald-700' => $item->status === 'dostepny',
    'bg-amber-100 text-amber-700' => $item->status === 'wypozyczony',
    'bg-red-100 text-red-700' => $item->status === 'w_naprawie',
    'bg-sky-100 text-sky-700' => $item->status === 'do_sprzedazy',
    'bg-gray-100 text-gray-600' => in_array($item->status, ['sprzedany', 'wycofany']),
])>{{ $item->statusLabel() }}</span>
