<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Item;
use App\Models\SaleListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_is_not_found_when_disabled(): void
    {
        $this->get(route('marketplace.index'))->assertNotFound();
    }

    public function test_marketplace_lists_only_active_listings_for_guests(): void
    {
        AppSetting::current()->fill([
            'public_marketplace_enabled' => true,
            'public_contact_email' => 'kontakt@example.com',
        ])->save();

        $listed = $this->createListing('NAR-BRAK-2026-00001', 'Wiertarka na sprzedaż', 'wyeksportowana');
        $sold = $this->createListing('NAR-BRAK-2026-00002', 'Sprzedana szlifierka', 'sprzedana');
        $draft = $this->createListing('NAR-BRAK-2026-00003', 'Szkic oferty', 'szkic');

        $response = $this->get(route('marketplace.index'));

        $response->assertOk();
        $response->assertSee($listed->title);
        $response->assertDontSee($sold->title);
        $response->assertDontSee($draft->title);
        $response->assertSee('kontakt@example.com');
    }

    public function test_marketplace_view_toggle_is_remembered_in_its_own_session_key(): void
    {
        AppSetting::current()->fill(['public_marketplace_enabled' => true])->save();

        $this->get(route('marketplace.index', ['view' => 'list']))->assertOk();

        $this->assertSame('list', session('marketplace_view'));
    }

    public function test_marketplace_can_be_filtered_by_category(): void
    {
        AppSetting::current()->fill(['public_marketplace_enabled' => true])->save();
        $tools = Category::create(['name' => 'Narzędzia', 'code' => 'NAR']);
        $electronics = Category::create(['name' => 'Elektronika', 'code' => 'ELE']);

        $drill = $this->createListing('NAR-BRAK-2026-00001', 'Wiertarka', 'wyeksportowana', $tools->id);
        $multimeter = $this->createListing('ELE-BRAK-2026-00001', 'Multimetr', 'wyeksportowana', $electronics->id);

        $response = $this->get(route('marketplace.index', ['category_id' => $tools->id]));

        $response->assertOk();
        $response->assertSee($drill->title);
        $response->assertDontSee($multimeter->title);
        $response->assertSee('Narzędzia');
        $response->assertSee('Elektronika');
    }

    /**
     * Lista pokazuje tylko okładkę (is_primary) i licznik zdjęć — całą
     * galerię ma strona oferty (patrz test_product_page_shows_...). Obie
     * strony przełącznika widoku, bo to dwa niezależne bloki markupu.
     */
    public function test_marketplace_list_shows_the_cover_photo_and_photo_count_in_both_views(): void
    {
        Storage::fake('public');
        AppSetting::current()->fill(['public_marketplace_enabled' => true])->save();
        $listing = $this->createListingWithPhotos(['main-cover.jpg', 'second-photo.jpg', 'third-photo.jpg']);

        foreach (['grid', 'list'] as $view) {
            $this->get(route('marketplace.index', ['view' => $view]))
                ->assertOk()
                ->assertSee($listing->title)
                ->assertSee('main-cover.jpg')
                ->assertDontSee('second-photo.jpg')
                ->assertSee('📷 3');
        }
    }

    /** Item::photosForGallery() musi dawać okładkę (is_primary) na pierwszym miejscu, niezależnie od sort_order. */
    public function test_gallery_photo_order_puts_the_primary_photo_first(): void
    {
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'dostepny',
        ]);
        $item->photos()->create(['path' => 'items/1/first-added.jpg', 'is_primary' => false, 'sort_order' => 0]);
        $item->photos()->create(['path' => 'items/1/actual-cover.jpg', 'is_primary' => true, 'sort_order' => 1]);

        $ordered = $item->photosForGallery();

        $this->assertSame('items/1/actual-cover.jpg', $ordered->first()->path);
    }

    /** Na liście zamiast przycisku OLX/Allegro jest "Więcej informacji" — link zewnętrzny dopiero na stronie oferty. */
    public function test_external_listing_button_is_shown_only_on_the_product_page_when_link_was_given(): void
    {
        AppSetting::current()->fill(['public_marketplace_enabled' => true])->save();
        $withLink = $this->createListing('NAR-BRAK-2026-00001', 'Wiertarka z linkiem', 'wyeksportowana');
        $withLink->update(['external_url' => 'https://www.olx.pl/d/oferta/wiertarka-CID99-ID123.html']);
        $withoutLink = $this->createListing('NAR-BRAK-2026-00002', 'Szlifierka bez linku', 'wyeksportowana');

        foreach (['grid', 'list'] as $view) {
            $response = $this->get(route('marketplace.index', ['view' => $view]))->assertOk();
            $response->assertDontSee('olx.pl', false)->assertSee(__('More information'));
            $this->assertSame(2, substr_count($response->getContent(), __('More information')));
        }

        $this->get(route('marketplace.show', $withLink))
            ->assertSee('https://www.olx.pl/d/oferta/wiertarka-CID99-ID123.html', false)
            ->assertSee(__('View on :platform', ['platform' => 'OLX']));
        $this->get(route('marketplace.show', $withoutLink))
            ->assertDontSee('rel="noopener noreferrer nofollow"', false);
    }

    public function test_external_platform_name_is_detected_from_the_link_host(): void
    {
        $this->assertSame('Allegro', (new SaleListing(['external_url' => 'https://allegro.pl/oferta/123']))->externalPlatformName());
        $this->assertSame('OLX', (new SaleListing(['external_url' => 'https://m.olx.pl/d/oferta/x']))->externalPlatformName());
        $this->assertNull((new SaleListing(['external_url' => 'https://example.com/x']))->externalPlatformName());
        $this->assertNull((new SaleListing)->externalPlatformName());
    }

    public function test_description_is_shown_only_on_the_product_page_not_on_the_list(): void
    {
        AppSetting::current()->fill(['public_marketplace_enabled' => true])->save();
        $listing = $this->createListing('NAR-BRAK-2026-00001', 'Wiertarka', 'wyeksportowana');

        foreach (['grid', 'list'] as $view) {
            $this->get(route('marketplace.index', ['view' => $view]))
                ->assertSee('Wiertarka')
                ->assertSee($listing->item->conditionLabel())
                ->assertDontSee('Opis testowy');
        }

        $this->get(route('marketplace.show', $listing))->assertSee('Opis testowy');
    }

    public function test_listing_on_the_list_links_to_its_own_product_page(): void
    {
        AppSetting::current()->fill(['public_marketplace_enabled' => true])->save();
        $listing = $this->createListing('NAR-BRAK-2026-00001', 'Wiertarka', 'wyeksportowana');

        foreach (['grid', 'list'] as $view) {
            $this->get(route('marketplace.index', ['view' => $view]))
                ->assertOk()
                ->assertSee(route('marketplace.show', $listing), false);
        }
    }

    public function test_product_page_shows_full_description_every_photo_and_contact(): void
    {
        Storage::fake('public');
        AppSetting::current()->fill([
            'public_marketplace_enabled' => true,
            'public_contact_email' => 'kontakt@example.com',
        ])->save();
        $listing = $this->createListingWithPhotos(['main-cover.jpg', 'second-photo.jpg', 'third-photo.jpg']);
        $listing->update(['description' => "Linia pierwsza\nLinia druga", 'external_url' => 'https://allegro.pl/oferta/1']);

        $this->get(route('marketplace.show', $listing))
            ->assertOk()
            ->assertSee($listing->title)
            ->assertSee('Linia druga')
            ->assertSee('main-cover.jpg')
            ->assertSee('second-photo.jpg')
            ->assertSee('third-photo.jpg')
            ->assertSee('https://allegro.pl/oferta/1', false)
            ->assertSee('mailto:kontakt@example.com?subject=', false);
    }

    public function test_product_page_is_not_found_for_listings_not_currently_listed_or_when_disabled(): void
    {
        $listed = $this->createListing('NAR-BRAK-2026-00001', 'Wystawiona', 'wyeksportowana');
        $sold = $this->createListing('NAR-BRAK-2026-00002', 'Sprzedana', 'sprzedana');
        $draft = $this->createListing('NAR-BRAK-2026-00003', 'Szkic', 'szkic');

        $this->get(route('marketplace.show', $listed))->assertNotFound();

        AppSetting::current()->fill(['public_marketplace_enabled' => true])->save();

        $this->get(route('marketplace.show', $listed))->assertOk();
        $this->get(route('marketplace.show', $sold))->assertNotFound();
        $this->get(route('marketplace.show', $draft))->assertNotFound();
    }

    /** @param  array<int, string>  $filenames */
    private function createListingWithPhotos(array $filenames): SaleListing
    {
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'do_sprzedazy',
        ]);

        foreach ($filenames as $index => $filename) {
            $item->photos()->create([
                'path' => 'items/1/'.$filename, 'is_primary' => $index === 0, 'sort_order' => $index,
            ]);
        }

        return SaleListing::create([
            'item_id' => $item->id, 'platform' => 'olx', 'title' => 'Wiertarka - okazja',
            'description' => 'Opis testowy', 'price' => 90, 'status' => 'wyeksportowana', 'exported_at' => now(),
        ]);
    }

    private function createListing(string $inventoryNo, string $title, string $status, ?int $categoryId = null): SaleListing
    {
        $item = Item::create([
            'inventory_no' => $inventoryNo, 'name' => $title, 'condition' => 'uzywany', 'status' => 'do_sprzedazy',
            'category_id' => $categoryId,
        ]);

        return SaleListing::create([
            'item_id' => $item->id, 'platform' => 'olx', 'title' => $title,
            'description' => 'Opis testowy', 'price' => 99.99, 'status' => $status, 'exported_at' => now(),
        ]);
    }

    public function test_admin_can_enable_marketplace_with_contact_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('settings.app.update'), [
            'public_marketplace_enabled' => '1',
            'public_contact_email' => 'sprzedaz@example.com',
        ]);

        $response->assertRedirect();
        $setting = AppSetting::current();
        $this->assertTrue($setting->public_marketplace_enabled);
        $this->assertSame('sprzedaz@example.com', $setting->public_contact_email);
    }

    public function test_enabling_marketplace_without_contact_email_fails_validation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('settings.app.update'), [
            'public_marketplace_enabled' => '1',
        ]);

        $response->assertSessionHasErrors('public_contact_email');
        $this->assertFalse(AppSetting::current()->public_marketplace_enabled);
    }
}
