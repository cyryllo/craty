<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\SaleListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleListingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_moves_from_prepared_to_wystawione_after_export_then_to_sold(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany',
            'status' => 'dostepny', 'value' => 100,
        ]);
        $listing = $item->saleListings()->create([
            'platform' => 'olx', 'title' => 'Wiertarka - okazja', 'price' => 90,
        ]);

        // Zaraz po przygotowaniu: widoczna na "Przygotowane", nie na "Wystawione".
        $this->actingAs($magazynier)->get('/sprzedaz')->assertSee('Wiertarka - okazja');
        $this->actingAs($magazynier)->get('/sprzedaz/wystawione')->assertDontSee('Wiertarka - okazja');

        // Eksport CSV automatycznie przenosi ofertę do "Wystawione" i zmienia status przedmiotu.
        $this->actingAs($magazynier)->get('/sprzedaz/eksport.csv')->assertOk();

        $listing->refresh();
        $item->refresh();
        $this->assertSame('wyeksportowana', $listing->status);
        $this->assertNotNull($listing->exported_at);
        $this->assertSame('do_sprzedazy', $item->status);

        $this->actingAs($magazynier)->get('/sprzedaz')->assertDontSee('Wiertarka - okazja');
        $this->actingAs($magazynier)->get('/sprzedaz/wystawione')->assertSee('Wiertarka - okazja');

        // Oznaczenie jako sprzedane usuwa ją z "Wystawione".
        $this->actingAs($magazynier)
            ->post(route('sale-listings.mark-sold', $listing))
            ->assertRedirect();

        $this->assertSame('sprzedana', $listing->refresh()->status);
        $this->assertSame('sprzedany', $item->refresh()->status);
        $this->actingAs($magazynier)->get('/sprzedaz/wystawione')->assertDontSee('Wiertarka - okazja');
    }

    public function test_withdrawing_a_listing_removes_it_from_wystawione_and_frees_the_item(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'do_sprzedazy',
        ]);
        $listing = $item->saleListings()->create([
            'platform' => 'olx', 'title' => 'Wiertarka - okazja', 'status' => 'wyeksportowana', 'exported_at' => now(),
        ]);

        $this->actingAs($magazynier)
            ->post(route('sale-listings.withdraw', $listing))
            ->assertRedirect();

        $this->assertSame('wycofana', $listing->refresh()->status);
        $this->assertSame('dostepny', $item->refresh()->status);
        $this->actingAs($magazynier)->get('/sprzedaz/wystawione')->assertDontSee('Wiertarka - okazja');
    }

    public function test_marking_a_single_prepared_listing_as_listed_moves_it_to_wystawione(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'dostepny',
        ]);
        $listing = $item->saleListings()->create([
            'platform' => 'olx', 'title' => 'Wiertarka - okazja', 'price' => 90,
        ]);

        $this->actingAs($magazynier)
            ->post(route('sale-listings.mark-listed', $listing))
            ->assertRedirect();

        $this->assertSame('wyeksportowana', $listing->refresh()->status);
        $this->assertNotNull($listing->exported_at);
        $this->assertSame('do_sprzedazy', $item->refresh()->status);

        $this->actingAs($magazynier)->get('/sprzedaz')->assertDontSee('Wiertarka - okazja');
        $this->actingAs($magazynier)->get('/sprzedaz/wystawione')->assertSee('Wiertarka - okazja');
    }

    public function test_prepared_tab_has_a_direct_list_for_sale_button_per_row(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'do_sprzedazy',
        ]);
        $listing = $item->saleListings()->create(['platform' => 'olx', 'title' => 'Wiertarka - okazja', 'price' => 90]);

        $this->actingAs($magazynier)->get(route('sale-listings.index'))
            ->assertSee(__('list for sale'))
            ->assertSee(route('sale-listings.mark-listed', $listing), false);
    }

    public function test_item_page_shows_withdraw_and_mark_sold_instead_of_prepare_when_already_listed(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'do_sprzedazy',
        ]);
        $item->saleListings()->create([
            'platform' => 'olx', 'title' => 'Wiertarka - okazja', 'status' => 'wyeksportowana', 'exported_at' => now(),
        ]);

        $this->actingAs($magazynier)->get(route('items.show', $item))
            ->assertSee(__('withdraw'))
            ->assertSee(__('mark as sold'))
            ->assertDontSee(__('Prepare sale listing'));
    }

    public function test_item_page_shows_prepare_button_when_nothing_is_currently_listed(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'dostepny',
        ]);

        $this->actingAs($magazynier)->get(route('items.show', $item))
            ->assertSee(__('Prepare sale listing'));
    }

    public function test_cannot_mark_an_already_listed_listing_as_listed_again(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'do_sprzedazy',
        ]);
        $listing = $item->saleListings()->create([
            'platform' => 'olx', 'title' => 'Wiertarka - okazja', 'status' => 'wyeksportowana', 'exported_at' => now(),
        ]);

        $this->actingAs($magazynier)
            ->post(route('sale-listings.mark-listed', $listing))
            ->assertNotFound();
    }
}
