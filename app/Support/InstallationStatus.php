<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * Wspólna definicja "czy appka jest zainstalowana" — używana zarówno przez
 * EnsureNotInstalled (blokuje /install* po instalacji), jak i
 * RedirectToInstallerIfNotInstalled (kieruje na /install przed instalacją).
 * Każdy wyjątek (baza jeszcze nieskonfigurowana/nieosiągalna, tabela users
 * jeszcze nie istnieje) traktujemy jako "jeszcze niezainstalowane".
 */
class InstallationStatus
{
    public static function isInstalled(): bool
    {
        try {
            return Schema::hasTable('users') && User::query()->exists();
        } catch (\Throwable) {
            return false;
        }
    }
}
