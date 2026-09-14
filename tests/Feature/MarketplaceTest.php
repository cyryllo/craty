<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Item;
use App\Models\SaleListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
