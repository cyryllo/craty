@php($setting = \App\Models\AppSetting::current())
@if ($setting->logoUrl())
    <img src="{{ $setting->logoUrl() }}" alt="{{ $setting->effectiveName() }}" {{ $attributes->merge(['class' => 'object-contain']) }}>
@else
    {{-- Domyślne logo "Craty": regał z trzema półkami i przedmiotami na nich —
         ten sam rysunkowy styl co ikony w menu (x-icon), żeby appka miała
         spójną tożsamość zamiast domyślnego loga Laravela. --}}
    <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
        <rect x="4" y="4" width="24" height="24" rx="2" fill="none" stroke="currentColor" stroke-width="2"/>
        <line x1="4" y1="12" x2="28" y2="12" stroke="currentColor" stroke-width="2"/>
        <line x1="4" y1="20" x2="28" y2="20" stroke="currentColor" stroke-width="2"/>
        <rect x="7" y="6.5" width="5" height="5" fill="currentColor"/>
        <rect x="16.5" y="7.5" width="4" height="4" fill="currentColor"/>
        <rect x="7" y="14.5" width="14" height="5" fill="currentColor"/>
        <rect x="7" y="22.5" width="4" height="4" fill="currentColor"/>
        <rect x="14" y="21.5" width="7" height="5.5" fill="currentColor"/>
    </svg>
@endif
