@php($setting = \App\Models\AppSetting::current())
@if ($setting->logoUrl())
    <img src="{{ $setting->logoUrl() }}" alt="{{ $setting->effectiveName() }}" {{ $attributes->merge(['class' => 'object-contain']) }}>
@else
    {{-- Domyślne logo "Craty": skrzynka (rama, deski i ukośna belka), ten
         sam rysunek co ikona aplikacji i favicon (php artisan pwa:icons),
         tutaj jednokolorowy, żeby pasował do jasnego i ciemnego motywu. --}}
    {{-- fill="none" na KAŻDYM kształcie, nie tylko na <svg>: nawigacja daje
         logo klasę fill-current, a CSS nadpisuje atrybut na <svg>, więc ramka
         dziedziczyła wypełnienie i rysowała się jako pełny kwadrat (zgłoszone
         z produkcji, 2026-11-05). --}}
    <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
        <rect x="4" y="4" width="24" height="24" rx="2.5" fill="none" stroke="currentColor" stroke-width="3"/>
        <path d="M5.5 13.3h21M5.5 18.7h21" fill="none" stroke="currentColor" stroke-width="1.2" opacity=".55"/>
        <path d="M6 26 26 6" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endif
