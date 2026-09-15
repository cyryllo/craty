<?php

namespace App\Http\Middleware;

use App\Support\InstallationStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blokuje trasy /install* po tym, jak instalacja już się skończyła — pełna,
 * automatyczna blokada oparta na tym, czy w bazie istnieje jakikolwiek
 * użytkownik (nie osobna flaga/plik, patrz TODO.md "Instalator aplikacji").
 */
class EnsureNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (InstallationStatus::isInstalled()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
