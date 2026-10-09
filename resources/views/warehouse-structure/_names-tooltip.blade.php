{{-- Dymek z nazwami przedmiotów w danym miejscu — pokazuje się po najechaniu (albo fokusie z klawiatury) na
     element-rodzica z klasą "group relative". Sama lista, bez linków: kliknięcie w liczbę i tak otwiera pełną listę. --}}
@php($limit = 12)
<div role="tooltip"
     class="pointer-events-none absolute {{ ($align ?? 'left') === 'right' ? 'right-0' : 'left-0' }} top-full z-30 mt-1 hidden w-64 rounded-md bg-gray-900 px-3 py-2 text-left text-xs font-normal text-gray-100 shadow-lg group-hover:block group-focus-within:block dark:bg-black dark:ring-1 dark:ring-gray-700">
    @if (! empty($note))
        <p class="mb-1.5 italic text-gray-300">{{ $note }}</p>
    @endif
    @if ($names->isEmpty())
        <p class="text-gray-400">{{ __('Nothing here yet.') }}</p>
    @else
        <ul class="space-y-0.5">
            @foreach ($names->take($limit) as $name)
                <li class="truncate">• {{ $name }}</li>
            @endforeach
        </ul>
        @if ($names->count() > $limit)
            <p class="mt-1 text-gray-400">{{ __('…and :count more', ['count' => $names->count() - $limit]) }}</p>
        @endif
    @endif
</div>
