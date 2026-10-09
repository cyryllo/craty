{{-- Ramka "o sklepie": na stronie głównej opis sklepu z Ustawień sklepu (Markdown, już bezpiecznie przerobiony
     na HTML w AppSetting::markdown()) + kontakt; na stronie oferty sam kontakt, mail z tematem = tytuł oferty. --}}
@php
    $onListing = isset($listing) && $listing instanceof \App\Models\SaleListing;
    $description = $onListing ? '' : ($shopDescription ?? '');
@endphp
@if ($description || $contactEmail || $contactPhone)
    <div class="bg-indigo-50 border border-indigo-100 text-indigo-900 rounded-lg p-4 space-y-3 dark:bg-indigo-950 dark:text-indigo-300 dark:border-indigo-700">
        @if ($description)
            <div class="md-content text-sm">{!! $description !!}</div>
        @endif
        @if ($contactEmail || $contactPhone)
            <div class="flex flex-wrap items-center justify-between gap-3 @if ($description) pt-3 border-t border-indigo-100 dark:border-indigo-800 @endif">
                <p class="text-sm">{{ $onListing ? __('Interested in this item? Get in touch.') : __('Interested in one of the items below? Get in touch.') }}</p>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm font-medium">
                    @if ($contactEmail)
                        <a href="mailto:{{ $contactEmail }}{{ $onListing ? '?subject='.rawurlencode($listing->title) : '' }}" class="text-indigo-700 hover:underline dark:text-indigo-400">✉️ {{ $contactEmail }}</a>
                    @endif
                    @if ($contactPhone)
                        <a href="tel:{{ $contactPhone }}" class="text-indigo-700 hover:underline dark:text-indigo-400">📞 {{ $contactPhone }}</a>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endif
