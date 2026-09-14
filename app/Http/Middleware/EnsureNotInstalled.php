<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blokuje trasy /install* po tym, jak instalacja już się skończyła — pełna,
 * automatyczna blokada oparta na tym, czy w bazie istnieje jakikolwiek
 * użytkownik (nie osobna flaga/plik, patrz TODO.md "Instalator aplikacji").
 * Każdy wyjątek (baza jeszcze nieskonfigurowana/nieosiągalna, tabela users
 * jeszcze nie istnieje) traktujemy jako "jeszcze nie zainstalowane" — to
 * dokładnie stan appki tuż po pierwszym rozpakowaniu, zanim ktokolwiek
 * przejdzie przez kreator.
 */
class EnsureNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->alreadyInstalled()) {
            return redirect()->route('login');
        }

        return $next($request);
    }

    private function alreadyInstalled(): bool
    {
        try {
            return Schema::hasTable('users') && User::query()->exists();
        } catch (\Throwable) {
            return false;
        }
    }
}
