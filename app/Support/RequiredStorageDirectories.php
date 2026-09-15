<?php

namespace App\Support;

/**
 * Katalogi, które normalny `git clone`/`composer create-project` przynosi
 * puste, ale realnie obecne — Laravel trzyma w nich `.gitignore` właśnie po
 * to, żeby przeżyły checkout mimo braku prawdziwej zawartości. Paczka .zip z
 * Modułu Aktualizacje (`release:build`) świadomie NIE pakuje ich zawartości
 * (cache buduje się sam, sesje to dane usera — patrz UpdatePaths::
 * PACKAGE_EXCLUDES), więc na czystym rozpakowaniu tych katalogów w ogóle nie
 * ma. Bez nich wysypuje się KAŻDE żądanie z sesją/cache/skompilowanym
 * widokiem, jeszcze zanim Instalator zdąży cokolwiek pokazać —
 * `Illuminate\Filesystem::put()` nie tworzy brakujących katalogów
 * nadrzędnych samo z siebie. Wołane z `public/index.php`, PRZED startem
 * Laravela (stąd zwykła statyczna metoda, nie serwis w kontenerze).
 */
class RequiredStorageDirectories
{
    public const DIRECTORIES = [
        'framework/sessions',
        'framework/views',
        'framework/cache/data',
        'logs',
    ];

    /** @param  string  $storagePath  Ścieżka do storage/ appki (bez końcowego "/"). */
    public static function ensureExist(string $storagePath): void
    {
        foreach (self::DIRECTORIES as $dir) {
            $path = $storagePath.'/'.$dir;
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }
}
