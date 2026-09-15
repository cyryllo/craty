<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    /**
     * RedirectToInstallerIfNotInstalled odsyła KAŻDE żądanie na /install,
     * dopóki w bazie nie ma żadnego użytkownika — a prawie każdy istniejący
     * test niejawnie zakłada "appka jest zainstalowana" (nigdy nie musiał
     * tego zakładać jawnie, bo do tej pory nic tego nie sprawdzało). Zamiast
     * dopisywać User::factory()->create() na starcie każdego testu, zakładamy
     * tu jednego "milczącego" admina zaraz po migracji z RefreshDatabase —
     * niewidocznego dla testów, które i tak tworzą własnych userów
     * (żaden istniejący test nie liczy dokładnej liczby userów).
     *
     * Testy Instalatora (i te, które celowo sprawdzają stan "przed
     * instalacją") ustawiają `$this->withoutDefaultInstalledUser = true;`
     * w swoim setUp(), żeby to wyłączyć.
     */
    protected bool $withoutDefaultInstalledUser = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->withoutDefaultInstalledUser && Schema::hasTable('users') && ! User::query()->exists()) {
            User::factory()->create(['role' => 'admin'])->forceFill(['protected' => true])->save();
        }
    }
}
