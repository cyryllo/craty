<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Konto wyłączone przez admina (users.active = false) traci dostęp od razu,
 * także w już otwartej sesji — nie dopiero przy następnym logowaniu.
 * Wcześniej blokadę sprawdzał tylko middleware `role:` na trasach
 * zmieniających dane, więc wyłączony użytkownik dalej widział pulpit i listę
 * przedmiotów. Logowanie samo w sobie też odrzuca nieaktywne konta
 * (LoginRequest::authenticate()).
 */
class LogOutInactiveUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => __('This account has been deactivated.')]);
        }

        return $next($request);
    }
}
