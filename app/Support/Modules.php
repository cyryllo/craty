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
        'loans' => [
            'label' => 'Loans',
            'description' => 'Lending items to people: single loans, kits, reservations for days, PDF issue and return protocols, the Loans page and overdue reminders.',
        ],
        'sales' => [
            'label' => 'Sale',
            'description' => 'Sale listings, the "Sale" section, and the public flea market page.',
        ],
        'contacts' => [
            'label' => 'Contacts',
            'description' => 'People, companies and repair services you lend equipment to, with their loan history. When off, "loaned to" is plain text.',
        ],
        'service' => [
            'label' => 'Service',
            'description' => 'Repairs (sending items to a repair service or your own workshop), periodic inspections and warranty dates, with reminders on the dashboard.',
        ],
        'consumables' => [
            'label' => 'Consumables',
            'description' => 'Things counted by quantity (screws, glue, cable) on their own list, with a minimum stock level, a warning when running low and a history of who took what.',
        ],
        'bookings' => [
            'label' => 'Machine booking',
            'description' => 'Booking time on stationary items (3D printers, laser cutters, workstations) in a weekly calendar. Accounts with the Member role can book on their own and see only this.',
        ],
    ];

    public static function isEnabled(string $key): bool
    {
        $column = "module_{$key}_enabled";

        return (bool) (AppSetting::current()->{$column} ?? false);
    }
}
