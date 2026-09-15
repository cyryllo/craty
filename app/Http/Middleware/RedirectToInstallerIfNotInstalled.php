<?php

namespace App\Http\Middleware;

use App\Support\InstallationStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Odwrotność EnsureNotInstalled — jeśli appka jeszcze nie jest zainstalowana,
 * każde inne żądanie (login, dashboard, cokolwiek) ma trafić prosto na
 * kreator, zamiast pokazywać ekrany, które i tak nie zadziałają bez
 * zainstalowanej bazy (albo, gorzej, wywalać nieczytelny błąd 500).
 */
class RedirectToInstallerIfNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        // /install* samo się obsługuje (patrz EnsureNotInstalled), a /up to
        // health-check Laravela — żadne z nich nie ma być przekierowywane.
        if ($request->is('install*', 'up') || InstallationStatus::isInstalled()) {
            return $next($request);
        }

        return redirect()->route('install.show');
    }
}
