<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ustala język na każde żądanie: preferencja osobista zalogowanego
 * użytkownika → globalny domyślny język ustawiony przez admina
 * (AppSetting) → config('app.locale') (APP_LOCALE, domyślnie "en").
 *
 * Uruchamia się na każde żądanie w grupie "web", więc musi przetrwać sytuację,
 * gdy tabela app_settings jeszcze nie istnieje (świeża instalacja przed
 * migracją, testy bez RefreshDatabase) — wtedy po prostu pomija globalne
 * ustawienie i leci dalej na preferencji użytkownika / configu.
 */
class SetLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $globalDefault = Schema::hasTable('app_settings') ? AppSetting::current()->locale : null;

        $locale = $request->user()?->locale ?? $globalDefault ?? config('app.locale');

        if (array_key_exists($locale, AppSetting::LOCALES)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
