<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Support\PwaIcons;

/**
 * Manifest PWA generowany przez aplikację (wcześniej statyczny manifest.json):
 * nazwa z Ustawień i ikony z własnego logo, gdy admin to zaznaczy. Pod nowym
 * adresem /manifest.webmanifest, bo aktualizacja paczką nie usuwa plików, a
 * stary manifest.json zostawiony na serwerze przesłaniałby trasę o tej samej
 * nazwie (Apache serwuje istniejący plik, zanim żądanie dotrze do Laravela).
 * "id" bez zmian (/dashboard), więc zainstalowana aplikacja zostaje tą samą.
 */
class ManifestController extends Controller
{
    public function __invoke()
    {
        $name = AppSetting::current()->effectiveName();

        $icons = [];
        foreach (['any', 'maskable'] as $purpose) {
            foreach ([192, 512] as $size) {
                $icons[] = ['src' => PwaIcons::url($size), 'sizes' => "{$size}x{$size}", 'type' => 'image/png', 'purpose' => $purpose];
            }
        }

        return response()->json([
            'id' => '/dashboard',
            'name' => $name,
            'short_name' => mb_substr($name, 0, 12),
            'start_url' => '/dashboard',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#111827',
            'theme_color' => '#111827',
            'icons' => $icons,
        ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
