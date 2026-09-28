{{-- Kontakt (mailto/tel) — na liście ogólny, na stronie oferty z tematem maila = tytuł oferty. --}}
@if ($contactEmail || $contactPhone)
    <div class="bg-indigo-50 border border-indigo-100 text-indigo-900 rounded-lg p-4 flex flex-wrap items-center justify-between gap-3 dark:bg-indigo-950 dark:text-indigo-300 dark:border-indigo-700">
        <p class="text-sm">{{ isset($listing) && $listing instanceof \App\Models\SaleListing ? __('Interested in this item? Get in touch.') : __('Interested in one of the items below? Get in touch.') }}</p>
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm font-medium">
            @if ($contactEmail)
                <a href="mailto:{{ $contactEmail }}{{ isset($listing) && $listing instanceof \App\Models\SaleListing ? '?subject='.rawurlencode($listing->title) : '' }}" class="text-indigo-700 hover:underline dark:text-indigo-400">✉️ {{ $contactEmail }}</a>
            @endif
            @if ($contactPhone)
                <a href="tel:{{ $contactPhone }}" class="text-indigo-700 hover:underline dark:text-indigo-400">📞 {{ $contactPhone }}</a>
            @endif
        </div>
    </div>
@endif
