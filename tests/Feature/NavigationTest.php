<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Życzenie użytkownika (2026-09-16) — obok loga w wersji mobilnej appka
 * pokazuje teraz ikony głównych pozycji menu (Panel/Przedmioty/Sprzedaż),
 * żeby dało się do nich dotrzeć bez otwierania rozwijanego menu z
 * hamburgera. Patrz layouts/navigation.blade.php.
 */
class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_mobile_icon_shortcuts_next_to_the_logo(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('title="'.__('Panel').'"', false)
            ->assertSee('title="'.__('Items').'"', false)
            ->assertSee('title="'.__('Sale').'"', false);
    }
}
