<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Services\AppIconFromLogo;
use Illuminate\Support\Facades\Storage;

/**
 * Adresy ikon aplikacji (manifest PWA, apple-touch-icon, domyślny favicon).
 *
 * Domyślne ikony (skrzynka, `php artisan pwa:icons`) mają w adresie numer
 * wersji: telefony i przeglądarki trzymają ikonę po adresie, więc po
 * przerysowaniu ikon bez zmiany adresu dalej pokazywały starą (zgłoszone
 * 2026-11-05). Po każdej zmianie domyślnych ikon podbij VERSION.
 *
 * Gdy admin zaznaczy "Użyj logo jako ikony aplikacji", adres wskazuje ikonę
 * zrobioną z logo (AppIconFromLogo); jej nazwa zmienia się razem z logo.
 */
class PwaIcons
{
    public const VERSION = 2;

    public static function url(int $size): string
    {
        $custom = AppIconFromLogo::path(AppSetting::current(), $size);
        if ($custom && Storage::disk('public')->exists($custom)) {
            return Storage::disk('public')->url($custom);
        }

        return "/pwa-icons/icon-{$size}.png?v=".self::VERSION;
    }
}
