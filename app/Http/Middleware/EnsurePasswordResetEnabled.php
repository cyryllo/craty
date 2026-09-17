<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gdy admin wyłączy samoobsługowy reset hasła w Ustawienia → Powiadomienia,
 * trasy password.request/.email/.reset/.store mają dawać 404, tak samo jak
 * inne wyłączone funkcje w appce (patrz EnsureModuleEnabled) — użytkownik
 * musi wtedy poprosić admina o ręczny reset hasła przez /users.
 */
class EnsurePasswordResetEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(AppSetting::current()->password_reset_enabled, 404);

        return $next($request);
    }
}
