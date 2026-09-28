{{-- Znacznik "📷 3" na miniaturze, gdy oferta ma więcej niż jedno zdjęcie — całą galerię pokazuje strona oferty. --}}
@if ($photos->count() > 1)
    <span class="absolute bottom-1 right-1 bg-black/60 text-white text-[11px] leading-none px-1.5 py-1 rounded pointer-events-none">📷 {{ $photos->count() }}</span>
@endif
