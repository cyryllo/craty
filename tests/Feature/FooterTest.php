<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use App\Support\AppVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Stopka (layouts/_footer.blade.php) jest @include'owana osobno w każdej z
 * czterech niezależnych "powłok" HTML appki (nie ma jednego wspólnego
 * layoutu bazowego) — po jednym teście na powłokę, żeby złapać regresję,
 * gdyby ktoś dodał nową stronę z własnym <html> i zapomniał o stopce.
 */
class FooterTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_appears_on_authenticated_app_pages(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertSee('Craty v'.app(AppVersion::class)->current())
            ->assertSee('https://github.com/cyryllo/craty', false);
    }

    public function test_footer_appears_on_guest_pages(): void
    {
        $this->get(route('login'))
            ->assertSee('Craty v'.app(AppVersion::class)->current())
            ->assertSee('https://github.com/cyryllo/craty', false);
    }

    public function test_footer_appears_on_the_public_marketplace_page(): void
    {
        AppSetting::current()->fill(['public_marketplace_enabled' => true])->save();

        $this->get(route('marketplace.index'))
            ->assertSee('Craty v'.app(AppVersion::class)->current())
            ->assertSee('https://github.com/cyryllo/craty', false);
    }

    // Stopka na kreatorze instalacji jest przetestowana w InstallerTest —
    // ten test wymaga $withoutDefaultInstalledUser = true, ustawianego jako
    // właściwość klasy PRZED setUp() (a nie w ciele metody testowej), więc
    // musi siedzieć w klasie, która już to ma.
}
