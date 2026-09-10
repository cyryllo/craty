@props(['name'])
@switch($name)
    @case('language')
        {{-- globus: wybór języka --}}
        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4" {{ $attributes }}>
            <circle cx="10" cy="10" r="7"/>
            <ellipse cx="10" cy="10" rx="3" ry="7"/>
            <line x1="3" y1="10" x2="17" y2="10"/>
        </svg>
        @break

    @case('panel')
        {{-- domek: Panel/pulpit --}}
        <svg viewBox="0 0 20 20" fill="currentColor" {{ $attributes }}>
            <path d="M10 2.3 17.5 9V17H2.5V9Z"/>
        </svg>
        @break

    @case('items')
        {{-- karton/skrzynka: Przedmioty --}}
        <svg viewBox="0 0 20 20" fill="currentColor" {{ $attributes }}>
            <rect x="2" y="4" width="16" height="3.2" rx="1"/>
            <rect x="3" y="8.2" width="14" height="8" rx="1"/>
            <rect x="8" y="10.7" width="4" height="1.4" rx=".5" fill="#fff" fill-opacity=".55"/>
        </svg>
        @break

    @case('sale')
        {{-- metka z ceną: Sprzedaż --}}
        <svg viewBox="0 0 20 20" fill="currentColor" {{ $attributes }}>
            <path d="M3 3H11L17 10L11 17H3Z"/>
            <circle cx="6.5" cy="6.5" r="1.4" fill="#fff" fill-opacity=".6"/>
        </svg>
        @break

    @case('settings')
        {{-- suwaki: Ustawienia --}}
        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" {{ $attributes }}>
            <line x1="3" y1="5" x2="17" y2="5"/>
            <circle cx="8" cy="5" r="1.6" fill="currentColor" stroke="none"/>
            <line x1="3" y1="10" x2="17" y2="10"/>
            <circle cx="13" cy="10" r="1.6" fill="currentColor" stroke="none"/>
            <line x1="3" y1="15" x2="17" y2="15"/>
            <circle cx="6" cy="15" r="1.6" fill="currentColor" stroke="none"/>
        </svg>
        @break

    @case('profile')
        {{-- sylwetka: Profil --}}
        <svg viewBox="0 0 20 20" fill="currentColor" {{ $attributes }}>
            <circle cx="10" cy="6.5" r="3.2"/>
            <rect x="3.5" y="11.5" width="13" height="6.5" rx="4"/>
        </svg>
        @break

    @case('logout')
        {{-- drzwi ze strzałką: Wyloguj --}}
        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" {{ $attributes }}>
            <path d="M8 3H4.8a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1H8"/>
            <path d="M13 6.5 17 10l-4 3.5M17 10H7.5"/>
        </svg>
        @break

    @case('users')
        {{-- dwie sylwetki: Użytkownicy --}}
        <svg viewBox="0 0 20 20" fill="currentColor" {{ $attributes }}>
            <circle cx="14.2" cy="7.3" r="2" opacity=".7"/>
            <rect x="10.8" y="11.3" width="7" height="4.6" rx="2.3" opacity=".7"/>
            <circle cx="7" cy="6.2" r="2.6"/>
            <rect x="2.3" y="10.8" width="9.4" height="5.7" rx="2.85"/>
        </svg>
        @break

    @case('branding')
        {{-- ramka ze zdjęciem: Ustawienia aplikacji --}}
        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" {{ $attributes }}>
            <rect x="2.5" y="2.5" width="15" height="15" rx="1.8"/>
            <circle cx="7.3" cy="7.3" r="1.4" fill="currentColor" stroke="none"/>
            <path d="M4 15.5 8.2 11 9.6 12.4 13 9 16.5 12.5" stroke-linecap="round"/>
        </svg>
        @break

    @case('categories')
        {{-- teczka: Kategorie --}}
        <svg viewBox="0 0 20 20" fill="currentColor" {{ $attributes }}>
            <path d="M2.5 5.5H7.2L8.4 7.1H17.5V15.5H2.5Z"/>
        </svg>
        @break

    @case('warehouse')
        {{-- budynek magazynu: Magazyny --}}
        <svg viewBox="0 0 20 20" fill="currentColor" {{ $attributes }}>
            <path d="M2 8.5 10 3 18 8.5Z"/>
            <path d="M3 8.8H17V16.5H3Z"/>
            <rect x="8.5" y="12" width="3" height="4.5" fill="#fff" fill-opacity=".6"/>
        </svg>
        @break

    @case('location')
        {{-- pinezka mapy: Lokalizacje --}}
        <svg viewBox="0 0 20 20" fill="currentColor" {{ $attributes }}>
            <circle cx="10" cy="8" r="5.5"/>
            <path d="M6.5 13H13.5L10 18.5Z"/>
            <circle cx="10" cy="8" r="2" fill="#fff" fill-opacity=".7"/>
        </svg>
        @break
@endswitch
