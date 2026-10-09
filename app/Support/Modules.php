<?php

namespace App\Support;

use App\Models\AppSetting;

/**
 * Lekki rejestr modułów appki, które admin włącza/wyłącza w Ustawieniach →
 * Moduły — świadomie NIE pełny system pluginów (żadnej dynamicznej
 * rejestracji tras/serwisów) — tylko tyle, żeby dodanie kolejnego modułu
 * ("Serwis", "Materiały eksploatacyjne" — planowane, jeszcze nie zbudowane)
 * było dopisaniem wpisu tutaj + kolumny w `AppSetting` + owinięciem
 * odpowiednich tras/widoków w `Modules::isEnabled(...)`, a nie przebudową.
 *
 * Reszta appki (middleware `module:`, Blade, kontrolery) pyta WYŁĄCZNIE
 * przez `isEnabled()`, nigdy bezpośrednio o kolumnę na `AppSetting` — dzięki
 * temu ten plik jest jedynym miejscem, które wie, jak nazywa się kolumna
 * dla danego klucza modułu.
 */
class Modules
{
    /**
     * @var array<string, array{label: string, description: string}>
     *
     * Wartości `label`/`description` to teksty źródłowe do __() (klucze
     * angielskie), jak w Item::STATUSES — patrz settings/modules.blade.php.
     */
    public const MODULES = [
        'sales' => [
            'label' => 'Sale',
            'description' => 'Sale listings, the "Sale" section, and the public flea market page.',
        ],
    ];

    public static function isEnabled(string $key): bool
    {
        $column = "module_{$key}_enabled";

        return (bool) (AppSetting::current()->{$column} ?? false);
    }
}
