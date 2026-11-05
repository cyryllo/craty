<?php

namespace App\Support;

/**
 * Adresy ikon aplikacji (PWA, apple-touch-icon, domyślny favicon) z numerem
 * wersji. Telefony i przeglądarki trzymają ikonę po adresie: bez zmiany
 * adresu po przerysowaniu ikon (`php artisan pwa:icons`) dalej pokazują
 * starą (zgłoszone 2026-11-05 po zmianie ikony na skrzynkę).
 *
 * Po każdej zmianie ikon: podbij VERSION i ten sam numer w manifest.json
 * (statyczny plik, pilnuje tego PwaTest).
 */
class PwaIcons
{
    public const VERSION = 2;

    public static function url(int $size): string
    {
        return "/pwa-icons/icon-{$size}.png?v=".self::VERSION;
    }
}
