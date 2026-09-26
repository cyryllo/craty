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

    /**
     * "Specyfikacja techniczna" jako osobne pole zniknęła na życzenie
     * użytkownika (scalona z "Opis") — sugerowany opis oferty ma teraz
     * wklejać description przedmiotu, a EAN/wartość celowo znikły (EAN
     * zbędny na publicznym ogłoszeniu, wartość i tak ląduje osobno w polu
     * "cena").
     */
    public function test_suggested_sale_description_includes_item_description_not_ean_or_value(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany',
            'status' => 'dostepny', 'description' => 'Moc 600 W, uchwyt 13 mm', 'ean' => '5901234123457', 'value' => 250,
        ]);

        $response = $this->actingAs($magazynier)->get(route('items.sale-listing.create', $item));

        $response->assertSee('Moc 600 W, uchwyt 13 mm', false)
            ->assertSee($item->conditionLabel())
            ->assertDontSee('5901234123457')
            ->assertDontSee(__('Estimated value'));
    }

    public function test_sale_listing_can_be_saved_with_an_optional_external_link(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'dostepny',
        ]);

        $this->actingAs($magazynier)->get(route('items.sale-listing.create', $item))
            ->assertSee('name="external_url"', false)
            ->assertDontSee('<x-text-input', false);

        $this->actingAs($magazynier)->post(route('items.sale-listing.store', $item), [
            'platform' => 'allegro', 'title' => 'Wiertarka', 'external_url' => 'https://allegro.pl/oferta/wiertarka-123',
        ])->assertRedirect(route('sale-listings.index'));

        $this->assertSame('https://allegro.pl/oferta/wiertarka-123', $item->saleListings()->first()->external_url);

        $this->actingAs($magazynier)->post(route('items.sale-listing.store', $item), [
            'platform' => 'olx', 'title' => 'Bez linku',
        ])->assertSessionHasNoErrors();
        $this->assertNull($item->saleListings()->where('title', 'Bez linku')->first()->external_url);
    }

    public function test_external_link_must_be_a_http_url(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'dostepny',
        ]);

        $this->actingAs($magazynier)->post(route('items.sale-listing.store', $item), [
            'platform' => 'olx', 'title' => 'Wiertarka', 'external_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('external_url');

        $this->assertSame(0, $item->saleListings()->count());
    }

    public function test_external_link_can_be_added_later_from_prepared_and_listed_tabs_and_cleared(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'do_sprzedazy',
        ]);
        $draft = $item->saleListings()->create(['platform' => 'olx', 'title' => 'Szkic', 'status' => 'szkic']);
        $listed = $item->saleListings()->create(['platform' => 'olx', 'title' => 'Wystawiona', 'status' => 'wyeksportowana', 'exported_at' => now()]);

        $this->actingAs($magazynier)->get(route('sale-listings.index'))
            ->assertOk()->assertSee('name="external_url"', false);
        $this->actingAs($magazynier)->get(route('sale-listings.exported'))
            ->assertOk()->assertSee('name="external_url"', false);

        $this->actingAs($magazynier)->patch(route('sale-listings.update-link', $listed), [
            'external_url' => 'https://www.olx.pl/d/oferta/wiertarka-123.html',
        ])->assertSessionHasNoErrors();
        $this->assertSame('https://www.olx.pl/d/oferta/wiertarka-123.html', $listed->fresh()->external_url);

        $this->actingAs($magazynier)->get(route('sale-listings.exported'))
            ->assertSee('🔗', false);

        $this->actingAs($magazynier)->patch(route('sale-listings.update-link', $listed), ['external_url' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull($listed->fresh()->external_url);

        $this->actingAs($magazynier)->patch(route('sale-listings.update-link', $draft), ['external_url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('external_url');
        $this->assertNull($draft->fresh()->external_url);
    }

    public function test_external_link_cannot_be_edited_on_a_sold_listing(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'sprzedany',
        ]);
        $sold = $item->saleListings()->create(['platform' => 'olx', 'title' => 'Sprzedana', 'status' => 'sprzedana']);

        $this->actingAs($magazynier)->patch(route('sale-listings.update-link', $sold), ['external_url' => 'https://allegro.pl/x'])
            ->assertNotFound();
    }

    public function test_existing_listing_can_be_edited_including_platform_without_creating_a_duplicate(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'do_sprzedazy',
        ]);
        $listed = $item->saleListings()->create([
            'platform' => 'allegro', 'title' => 'Stary tytuł', 'status' => 'wyeksportowana', 'exported_at' => now(),
        ]);

        $this->actingAs($magazynier)->get(route('sale-listings.exported'))
            ->assertSee(route('sale-listings.edit', $listed), false);

        $this->actingAs($magazynier)->get(route('sale-listings.edit', $listed))
            ->assertOk()
            ->assertSee('Stary tytuł')
            ->assertSee('<option value="allegro" selected', false)
            ->assertDontSee('<x-', false);

        $this->actingAs($magazynier)->put(route('sale-listings.update', $listed), [
            'platform' => 'olx', 'title' => 'Nowy tytuł', 'price' => 120, 'external_url' => 'https://www.olx.pl/d/oferta/x.html',
        ])->assertRedirect(route('sale-listings.exported'));

        $listed->refresh();
        $this->assertSame('olx', $listed->platform);
        $this->assertSame('Nowy tytuł', $listed->title);
        $this->assertSame('wyeksportowana', $listed->status);
        $this->assertSame(1, $item->saleListings()->count());
    }

    public function test_sold_listing_cannot_be_edited(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'sprzedany',
        ]);
        $sold = $item->saleListings()->create(['platform' => 'olx', 'title' => 'Sprzedana', 'status' => 'sprzedana']);

        $this->actingAs($magazynier)->get(route('sale-listings.edit', $sold))->assertNotFound();
        $this->actingAs($magazynier)->put(route('sale-listings.update', $sold), ['platform' => 'olx', 'title' => 'X'])->assertNotFound();
    }

    public function test_sale_tabs_link_to_the_flea_market_only_when_it_is_enabled(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($magazynier)->get(route('sale-listings.index'))
            ->assertDontSee(route('marketplace.index'), false);

        \App\Models\AppSetting::current()->fill(['public_marketplace_enabled' => true])->save();

        $this->actingAs($magazynier)->get(route('sale-listings.index'))->assertSee(route('marketplace.index'), false);
        $this->actingAs($magazynier)->get(route('sale-listings.exported'))->assertSee(route('marketplace.index'), false);
    }

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
        $this->actingAs($magazynier)->get('/sales')->assertSee('Wiertarka - okazja');
        $this->actingAs($magazynier)->get('/sales/wystawione')->assertDontSee('Wiertarka - okazja');

        // Eksport CSV automatycznie przenosi ofertę do "Wystawione" i zmienia status przedmiotu.
        $this->actingAs($magazynier)->get('/sales/eksport.csv')->assertOk();

        $listing->refresh();
        $item->refresh();
        $this->assertSame('wyeksportowana', $listing->status);
        $this->assertNotNull($listing->exported_at);
        $this->assertSame('do_sprzedazy', $item->status);

        $this->actingAs($magazynier)->get('/sales')->assertDontSee('Wiertarka - okazja');
        $this->actingAs($magazynier)->get('/sales/wystawione')->assertSee('Wiertarka - okazja');

        // Oznaczenie jako sprzedane usuwa ją z "Wystawione".
        $this->actingAs($magazynier)
            ->post(route('sale-listings.mark-sold', $listing))
            ->assertRedirect();

        $this->assertSame('sprzedana', $listing->refresh()->status);
        $this->assertSame('sprzedany', $item->refresh()->status);
        $this->actingAs($magazynier)->get('/sales/wystawione')->assertDontSee('Wiertarka - okazja');
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
        $this->actingAs($magazynier)->get('/sales/wystawione')->assertDontSee('Wiertarka - okazja');
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

        $this->actingAs($magazynier)->get('/sales')->assertDontSee('Wiertarka - okazja');
        $this->actingAs($magazynier)->get('/sales/wystawione')->assertSee('Wiertarka - okazja');
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

    public function test_exported_tab_shows_the_same_copy_preview_popup(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'do_sprzedazy',
        ]);
        $item->saleListings()->create([
            'platform' => 'olx', 'title' => 'Wiertarka - okazja', 'description' => 'Opis oferty', 'status' => 'wyeksportowana', 'exported_at' => now(),
        ]);

        $this->actingAs($magazynier)->get(route('sale-listings.exported'))
            ->assertSee('Wiertarka - okazja')
            ->assertSee('Opis oferty', false);
    }

    public function test_preview_popup_includes_item_photos_with_download_links(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'do_sprzedazy',
        ]);
        $photo = $item->photos()->create(['path' => 'items/1/a.jpg', 'is_primary' => true, 'sort_order' => 0]);
        $item->saleListings()->create(['platform' => 'olx', 'title' => 'Wiertarka - okazja', 'price' => 90]);

        // @js() koduje URL do wnętrza JSON.parse('...') z podwójnie
        // eskejpowanymi ukośnikami — sprawdzamy samą nazwę pliku zamiast
        // odtwarzać dokładny format eskejpowania.
        $this->actingAs($magazynier)->get(route('sale-listings.index'))
            ->assertSee('a.jpg');
    }

    public function test_item_page_shows_list_for_sale_and_withdraw_when_a_draft_listing_exists(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'do_sprzedazy',
        ]);
        $item->saleListings()->create(['platform' => 'olx', 'title' => 'Wiertarka - okazja']);

        $this->actingAs($magazynier)->get(route('items.show', $item))
            ->assertSee(__('list for sale'))
            ->assertSee(__('withdraw'))
            ->assertDontSee(__('Prepare sale listing'))
            ->assertDontSee(__('mark as sold'));
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
