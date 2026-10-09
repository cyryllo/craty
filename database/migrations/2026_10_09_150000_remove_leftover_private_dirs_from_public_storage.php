<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;

/**
 * Sprząta katalogi, które nie mają prawa leżeć w publicznym magazynie plików
 * (app-storage/app/public, dostępny z zewnątrz jako /storage/). Znalezione
 * realnie na produkcji: przy dawnej zmianie układu katalogów stary, prywatny
 * storage/ Laravela trafił do środka publicznego, razem z migawkami kodu z
 * usuniętej funkcji "cofnij aktualizację" (26 i 49 MB, do pobrania przez
 * każdego). Appka publicznie używa tylko items/, qr/ i branding/ — te trzy
 * nazwy poniżej nigdy nie są przez nią tworzone, więc usuwamy wyłącznie je.
 * Nieodwracalne celowo: to były śmieci, a nie dane użytkownika.
 */
return new class extends Migration
{
    private const LEFTOVER_DIRS = ['app', 'framework', 'logs'];

    public function up(): void
    {
        $public = storage_path('app/public');

        foreach (self::LEFTOVER_DIRS as $dir) {
            $path = $public.DIRECTORY_SEPARATOR.$dir;

            if (is_dir($path) && ! is_link($path)) {
                File::deleteDirectory($path);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
