<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ustala język na każde żądanie: preferencja osobista zalogowanego
 * użytkownika → globalny domyślny język ustawiony przez admina
 * (AppSetting) → config('app.locale') (APP_LOCALE, domyślnie "en").
 *
 * Uruchamia się na każde żądanie w grupie "web", więc musi przetrwać zarówno
 * "tabela app_settings jeszcze nie istnieje" (świeża instalacja przed
 * migracją, testy bez RefreshDatabase), jak i "baza w ogóle nieosiągalna"
 * (czysty .env sprzed przejścia kreatora) — wtedy po prostu pomija globalne
 * ustawienie i leci dalej na preferencji użytkownika / configu. Patrz
 * AppSetting::current()'s own try/catch, na którym to się opiera.
 */
class SetLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // AppSetting::current() sam w sobie jest już odporne na "tabela
        // jeszcze nie istnieje" ORAZ na "baza w ogóle nieosiągalna" (patrz
        // jego własny try/catch) — więc wołamy je bezpośrednio zamiast
        // powtarzać tu Schema::hasTable() na własną rękę. To drugie realnie
        // rzucało wyjątkiem samo w sobie (próbuje otworzyć połączenie, żeby
        // odpowiedzieć), gdy DB_HOST w ogóle nie istnieje — czysty .env
        // sprzed przejścia kreatora (np. "db" z szablonu Dockera na realnym
        // hostingu) wywalał GET /install 500-tką, zanim admin zdążył
        // cokolwiek wpisać.
        $globalDefault = AppSetting::current()->locale;

        $locale = $request->user()?->locale ?? $globalDefault ?? config('app.locale');

        if (array_key_exists($locale, AppSetting::LOCALES)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
