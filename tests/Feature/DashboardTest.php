<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_items_tile_links_to_the_items_list(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('items.index'), false);
    }

    public function test_loaned_out_tile_links_to_items_filtered_by_status(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('items.index', ['status' => 'wypozyczony']), false);
    }

    public function test_for_sale_tile_links_to_the_exported_sale_listings_page(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('sale-listings.exported'), false);
    }

    public function test_total_value_tile_is_not_a_link(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        // Karta "Total value" jest jedynym <div>, reszta trzech kafelków to <a> — patrz dashboard.blade.php.
        $this->assertMatchesRegularExpression(
            '/<div class="bg-white rounded-lg shadow p-5 dark:bg-gray-800">\s*<p class="text-sm text-gray-500 dark:text-gray-400">'.preg_quote(__('Total value'), '/').'<\/p>/',
            $response->getContent()
        );
    }

    /**
     * Regresja: dashboard i status "Wypożyczony" (etykieta pojedynczego
     * przedmiotu, rodzaj męski) współdzielą ten sam angielski klucz "On
     * loan" tylko po stronie Item::STATUSES — kafelek dashboardu celowo
     * używa OSOBNEGO klucza "Loaned out" ("Wypożyczone"), żeby zmiana
     * gramatyczna formy kafelka nie popsuła etykiety statusu przedmiotu.
     */
    public function test_loaned_out_tile_uses_its_own_translation_key_not_the_item_status_label(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);
        Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka',
            'condition' => 'nowy', 'status' => 'wypozyczony',
        ]);

        $dashboard = $this->actingAs($user)->get(route('dashboard'));
        $itemShow = $this->actingAs($user)->get(route('items.index'));

        $dashboard->assertSee(__('Loaned out'));
        $itemShow->assertSee(__('On loan'));
    }

    public function test_recently_added_column_shows_only_the_item_name(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);
        Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00002', 'name' => 'Śrubokręt',
            'condition' => 'nowy', 'status' => 'dostepny',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Śrubokręt')
            ->assertDontSee('NAR-BRAK-2026-00002');
    }

    public function test_added_from_phone_column_lists_items_needing_completion(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);
        $quickAdded = Item::create([
            'inventory_no' => 'GEN-BRAK-2026-00003', 'name' => 'Zeskanowany przedmiot',
            'condition' => 'nowy', 'status' => 'dostepny',
        ]);
        $quickAdded->forceFill(['needs_completion' => true])->save();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('Added from phone'))
            ->assertSee('Zeskanowany przedmiot');
    }

    /** Regresja: "Zwykły przedmiot" (bez needs_completion) ma prawo pojawić się w "Ostatnio dodane", ale NIE w "Dodane z telefonu". */
    public function test_a_regular_item_does_not_appear_in_the_added_from_phone_column(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);
        Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00004', 'name' => 'Zwykły przedmiot',
            'condition' => 'nowy', 'status' => 'dostepny',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk()->assertSee('Zwykły przedmiot');

        [, $addedFromPhoneColumn] = explode(__('Added from phone'), $response->getContent(), 2);
        $this->assertStringNotContainsString('Zwykły przedmiot', $addedFromPhoneColumn);
    }

    public function test_added_from_phone_column_shows_empty_state_when_nothing_needs_completion(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('Nothing to complete.'));
    }
}
