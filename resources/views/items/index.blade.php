<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Items') }}</h2>
            @if (auth()->user()->isMagazynier())
                <div class="flex items-center gap-2">
                    <a href="{{ route('items.import.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        {{ __('Import from CSV') }}
                    </a>
                    <a href="{{ route('items.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                        + {{ __('Add item') }}
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Filtr miejsca przychodzi z liczb w Strukturze magazynu — pokazany jako znacznik z "×", a w formularzu niżej
             przenoszony ukrytymi polami, żeby wyszukiwanie/kategoria/status działały w obrębie tego miejsca. --}}
        @if ($locationFilter)
            <div class="flex flex-wrap items-center gap-2 text-sm">
                @if ($locationBackUrl)
                    <a href="{{ $locationBackUrl }}" class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-3 py-1 font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">&larr; {{ __('Warehouse structure') }}</a>
                @endif
                <span class="text-gray-500 dark:text-gray-400">{{ __('Location') }}:</span>
                <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 border border-indigo-200 px-3 py-1 text-indigo-800 dark:bg-indigo-950 dark:border-indigo-800 dark:text-indigo-300">
                    {{ $locationFilter }}
                    <a href="{{ $clearLocationUrl }}" class="font-semibold hover:text-indigo-950 dark:hover:text-white" aria-label="{{ __('Clear location filter') }}">&times;</a>
                </span>
                @if (auth()->user()->isMagazynier())
                    {{-- Inwentaryzacja dokładnie tego miejsca, które filtruje lista (te same parametry, LocationScope). --}}
                    <form method="POST" action="{{ route('stocktakes.store') }}">
                        @csrf
                        @foreach ($locationParams as $param => $value)
                            <input type="hidden" name="{{ $param }}" value="{{ $value }}">
                        @endforeach
                        <button class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-3 py-1 font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">📋 {{ __('Count this place') }}</button>
                    </form>
                @endif
            </div>
        @endif

        @if ($hasActiveFilters)
            {{-- Filtry są zapamiętywane (wracają po wejściu na listę z menu), więc
                 zawsze widać, że są włączone, i da się je wyczyścić jednym kliknięciem. --}}
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-md bg-amber-50 border border-amber-200 px-4 py-2 text-sm text-amber-900 dark:bg-amber-950 dark:border-amber-800 dark:text-amber-200">
                <span>🔎 {{ __('Filters are on and are remembered until you clear them.') }}</span>
                <a href="{{ route('items.index', ['clear' => 1]) }}" class="font-medium underline hover:no-underline">{{ __('Clear filters') }}</a>
            </div>
        @endif

        <form method="GET" class="bg-white rounded-lg shadow p-4 flex flex-wrap gap-3 items-end dark:bg-gray-800">
            @foreach (['storage_location_id', 'warehouse_id', 'room_id', 'rack', 'shelf'] as $locationParam)
                @if (request()->filled($locationParam))
                    <input type="hidden" name="{{ $locationParam }}" value="{{ request($locationParam) }}">
                @endif
            @endforeach
            <input type="hidden" name="view" value="{{ $view }}">
            {{-- "f": jawne użycie filtrów, nadpisuje zapamiętane (ItemController::index). --}}
            <input type="hidden" name="f" value="1">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="dir" value="{{ $dir }}">
            <a href="{{ route('scan.show') }}" title="{{ __('Scan') }}" class="inline-flex items-center justify-center w-9 h-9 shrink-0 rounded-md border border-gray-300 text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700">
                <x-icon name="scan" class="w-4 h-4" />
            </a>
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">{{ __('Search') }}</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('name, inventory no., serial no. or EAN') }}"
                       class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">
            </div>
            <div class="min-w-[160px]">
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">{{ __('Category') }}</label>
                <select name="category_id" class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">
                    <option value="">{{ __('all') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? null) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[160px]">
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">{{ __('Status') }}</label>
                <select name="status" class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">
                    <option value="">{{ __('all (in stock)') }}</option>
                    @foreach (\App\Models\Item::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) == $value)>{{ __($label) }}</option>
                    @endforeach
                </select>
            </div>
            <button class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">{{ __('Filter') }}</button>
            @if ($hasActiveFilters)
                <a href="{{ route('items.index', ['clear' => 1]) }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">{{ __('clear') }}</a>
            @endif

            @if ($view === 'grid')
                {{-- W kafelkach nie ma nagłówków kolumn, więc sortowanie z listy. --}}
                <div class="min-w-[160px]">
                    <label for="grid-sort" class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">{{ __('Sort') }}</label>
                    <select id="grid-sort" onchange="window.location = this.value" class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">
                        @foreach ([['created', 'desc', __('Newest first')], ['created', 'asc', __('Oldest first')], ['name', 'asc', __('Name A–Z')], ['name', 'desc', __('Name Z–A')], ['value', 'desc', __('Highest value')], ['value', 'asc', __('Lowest value')]] as [$column, $direction, $label])
                            <option value="{{ \App\Http\Controllers\ItemController::sortLink($column, $direction) }}" @selected($sort === $column && $dir === $direction)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="ms-auto flex rounded-md border border-gray-300 overflow-hidden shrink-0 dark:border-gray-600" role="group" aria-label="{{ __('List view') }}">
                <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}"
                   title="{{ __('Grid view') }}"
                   @class(['px-3 py-2 text-sm', 'bg-gray-900 text-white' => $view === 'grid', 'bg-white text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700' => $view !== 'grid'])>
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path d="M3 3h6v6H3V3zm8 0h6v6h-6V3zM3 11h6v6H3v-6zm8 0h6v6h-6v-6z"/></svg>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}"
                   title="{{ __('List view') }}"
                   @class(['px-3 py-2 text-sm border-l border-gray-300 dark:border-gray-600', 'bg-gray-900 text-white' => $view === 'list', 'bg-white text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700' => $view !== 'list'])>
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 6a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 6a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                </a>
            </div>
        </form>

        {{-- Druk etykiet zbiorczo — zaznacz checkboxami (dowolne, np. po
             przefiltrowaniu powyżej) i kliknij "Drukuj etykiety". Szablon i
             cenę wybiera się dopiero NA PODGLĄDZIE (labels-print.blade.php),
             celowo nie tutaj — życzenie użytkownika. Osobny <form> od
             filtra wyżej (POST, otwiera się w nowej karcie, żeby nie
             stracić filtrów/widoku na tej stronie). Zaznaczenie działa
             tylko w obrębie aktualnej strony wyników (paginacja) — przy
             większej liczbie trzeba powtórzyć na kolejnej stronie. --}}
        <form method="POST" action="{{ route('items.labels.print') }}" target="_blank"
              onsubmit="if (!this.querySelector('.item-checkbox:checked')) { alert('{{ __('Select at least one item first.') }}'); return false; }">
            @csrf
            <div class="flex items-center justify-between gap-3 bg-white rounded-lg shadow p-3 mb-4 dark:bg-gray-800">
                <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <input type="checkbox" onclick="document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = this.checked)">
                    {{ __('Select all') }}
                </label>
                <div class="flex flex-wrap items-center gap-2">
                    @if (auth()->user()->isMagazynier() && \App\Support\Modules::isEnabled('loans'))
                        {{-- Ten sam formularz co druk etykiet, inny cel: zaznaczone przedmioty jako zestaw. --}}
                        <button type="submit" formaction="{{ route('loan-groups.create') }}" formmethod="GET" formtarget="_self"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                            📦 {{ __('Loan out selected') }}
                        </button>
                    @endif
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        🏷️ {{ __('Print labels') }}
                    </button>
                </div>
            </div>

        @if ($view === 'list')
            <div class="bg-white rounded-lg shadow overflow-x-auto dark:bg-gray-800">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase dark:bg-gray-900 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3 w-8"></th>
                            <th class="px-4 py-3"></th>
                            <th class="text-left px-4 py-3">@include('items._sort-header', ['column' => 'name', 'label' => __('Item name')])</th>
                            <th class="text-left px-4 py-3">@include('items._sort-header', ['column' => 'inventory_no', 'label' => __('Inventory no.')])</th>
                            <th class="text-left px-4 py-3">@include('items._sort-header', ['column' => 'category', 'label' => __('Category')])</th>
                            <th class="text-left px-4 py-3">@include('items._sort-header', ['column' => 'location', 'label' => __('Location')])</th>
                            <th class="text-left px-4 py-3">@include('items._sort-header', ['column' => 'value', 'label' => __('Value')])</th>
                            <th class="text-left px-4 py-3">@include('items._sort-header', ['column' => 'status', 'label' => __('Status')])</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($items as $item)
                            <tr class="hover:bg-gray-50 cursor-pointer dark:hover:bg-gray-700" onclick="window.location='{{ route('items.show', $item) }}'">
                                <td class="px-4 py-2 w-8" onclick="event.stopPropagation()">
                                    <input type="checkbox" name="items[]" value="{{ $item->id }}" class="item-checkbox">
                                </td>
                                <td class="px-4 py-2 w-10">
                                    <div class="relative w-8 h-8 rounded bg-gray-100 overflow-hidden flex items-center justify-center dark:bg-gray-700">
                                        @if ($item->primaryPhoto->first())
                                            <img src="{{ $item->primaryPhoto->first()->url() }}" alt="" class="w-full h-full object-cover">
                                        @else
                                            <x-photo-placeholder size="sm" />
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                    <a href="{{ route('items.show', $item) }}" class="hover:underline">{{ $item->name }}</a>
                                    @if ($item->needs_completion)
                                        <span title="{{ __('Quickly added by scan — needs category, location and the rest.') }}">📱</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 font-mono text-xs text-gray-400 dark:text-gray-500">{{ $item->inventory_no }}</td>
                                <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $item->category?->name ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $item->storageLocation?->label() ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $item->value ? number_format((float) $item->value, 0, ',', ' ').' zł' : '—' }}</td>
                                <td class="px-4 py-2">@include('items._status-badge', ['item' => $item])</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    {{ __('Nothing found.') }} @if (auth()->user()->isMagazynier())<a href="{{ route('items.create') }}" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Add the first item') }}</a>.@endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @forelse ($items as $item)
                    <div class="relative">
                        <input type="checkbox" name="items[]" value="{{ $item->id }}" class="item-checkbox absolute top-2 left-2 z-10 w-4 h-4 rounded shadow">
                        {{-- h-full: karta wypełnia całą komórkę siatki, więc wszystkie kafelki w rzędzie mają równą wysokość (dolna linia przez mt-auto). --}}
                        <a href="{{ route('items.show', $item) }}" class="h-full bg-white rounded-lg shadow hover:shadow-md transition overflow-hidden flex flex-col dark:bg-gray-800">
                        <div class="relative aspect-[4/3] shrink-0 bg-gray-100 flex items-center justify-center overflow-hidden dark:bg-gray-700">
                            @if ($item->primaryPhoto->first())
                                <img src="{{ $item->primaryPhoto->first()->url() }}" alt="" class="w-full h-full object-cover">
                            @else
                                <x-photo-placeholder />
                            @endif
                        </div>
                        <div class="p-4 flex-1 flex flex-col gap-1">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-medium text-gray-900 leading-snug dark:text-gray-100">
                                    {{ $item->name }}
                                    @if ($item->needs_completion)
                                        <span title="{{ __('Quickly added by scan — needs category, location and the rest.') }}">📱</span>
                                    @endif
                                </h3>
                                @include('items._status-badge', ['item' => $item])
                            </div>
                            <p class="text-xs font-mono text-gray-400 dark:text-gray-500">{{ $item->inventory_no }}</p>
                            <p class="text-sm text-gray-500 mt-auto pt-2 dark:text-gray-400">
                                {{ $item->category?->name ?? __('no category') }} · {{ $item->storageLocation?->label() ?? __('no location') }}
                            </p>
                        </div>
                        </a>
                    </div>
                @empty
                    <div class="col-span-full bg-white rounded-lg shadow p-8 text-center text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        {{ __('Nothing found.') }} @if (auth()->user()->isMagazynier())<a href="{{ route('items.create') }}" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Add the first item') }}</a>.@endif
                    </div>
                @endforelse
            </div>
        @endif
        </form>

        {{ $items->links() }}
    </div>
</x-app-layout>
