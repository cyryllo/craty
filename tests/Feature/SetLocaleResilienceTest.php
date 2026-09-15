<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regresja realnie złapana na produkcji: SetLocale sprawdzał
 * Schema::hasTable('app_settings') bezpośrednio, bez zabezpieczenia na
 * wypadek, gdy baza jest CAŁKOWICIE nieosiągalna (nie tylko "brakuje
 * tabeli") — a to dokładnie stan świeżego .env sprzed przejścia kreatora
 * instalacji (DB_HOST wciąż z szablonu, np. "db" z Dockera, wpisane na
 * realnym hostingu). Schema::hasTable() samo w sobie próbuje otworzyć
 * połączenie, więc rzucało wyjątkiem, zanim zdążyło odpowiedzieć "nie ma
 * tabeli" — GET /install wywalał się 500-tką, zanim admin zdążył cokolwiek
 * wpisać. AppSetting::current() ma własny try/catch na dokładnie to, ale
 * SetLocale go omijał, wołając Schema::hasTable() na własną rękę.
 */
class SetLocaleResilienceTest extends TestCase
{
    protected bool $withoutDefaultInstalledUser = true;

    public function test_it_does_not_throw_when_the_database_is_completely_unreachable(): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => 'this-host-does-not-exist.invalid',
        ]);
        DB::purge('mysql');

        $response = (new SetLocale)->handle(Request::create('/'), fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
    }
}
