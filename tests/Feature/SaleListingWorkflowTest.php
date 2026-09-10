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

    public function test_viewer_cannot_export_or_mark_sold(): void
    {
        $viewer = User::factory()->create(['role' => 'podglad']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'dostepny',
        ]);
        $listing = $item->saleListings()->create(['platform' => 'olx', 'title' => 'Wiertarka', 'status' => 'wyeksportowana']);

        $this->actingAs($viewer)->get('/sprzedaz')->assertForbidden();
        $this->actingAs($viewer)->get('/sprzedaz/eksport.csv')->assertForbidden();
        $this->actingAs($viewer)->post(route('sale-listings.mark-sold', $listing))->assertForbidden();
    }
}
