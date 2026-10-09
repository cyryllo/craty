<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Item;
use App\Models\SaleListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_only_admin_can_open_shop_settings_and_only_with_the_sale_module_on(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'magazynier']))
            ->get(route('settings.shop.edit'))->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('settings.shop.edit'))->assertOk();
        $this->actingAs($admin)->get(route('settings.index'))->assertSee(route('settings.shop.edit'), false);

        AppSetting::current()->fill(['module_sales_enabled' => false])->save();
        $this->actingAs($admin)->get(route('settings.shop.edit'))->assertNotFound();
        $this->actingAs($admin)->get(route('settings.index'))->assertDontSee(route('settings.shop.edit'), false);
    }

    public function test_admin_saves_description_contact_and_legal_texts(): void
    {
        $this->actingAs($this->admin())->post(route('settings.shop.update'), [
            'shop_description' => 'Sprzedajemy **sprzęt warsztatowy**.',
            'public_contact_email' => 'sprzedaz@example.com',
            'public_contact_phone' => '500 600 700',
            'privacy_policy' => '## Dane osobowe',
            'terms' => '',
        ])->assertSessionHasNoErrors();

        $setting = AppSetting::current();
        $this->assertSame('sprzedaz@example.com', $setting->public_contact_email);
        $this->assertSame('## Dane osobowe', $setting->privacy_policy);
        $this->assertNull($setting->terms);
    }

    public function test_home_page_shows_description_in_contact_box_and_legal_links_only_when_filled(): void
    {
        AppSetting::current()->fill([
            'shop_description' => 'Odbiór osobisty **w Poznaniu**.',
            'public_contact_email' => 'sprzedaz@example.com',
            'privacy_policy' => "## Administrator danych\n\nTreść polityki.",
        ])->save();

        $this->get('/')
            ->assertSee('<strong>w Poznaniu</strong>', false)
            ->assertSee(__('Privacy policy'))
            ->assertSee('<h2>Administrator danych</h2>', false)
            ->assertDontSee(__('Terms and conditions'));

        // Opis tylko na stronie głównej, nie na stronie oferty.
        $item = Item::create(['inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'do_sprzedazy']);
        $listing = SaleListing::create(['item_id' => $item->id, 'platform' => 'olx', 'title' => 'Wiertarka', 'price' => 10, 'status' => 'wyeksportowana', 'exported_at' => now()]);
        $this->get(route('marketplace.show', $listing))
            ->assertDontSee('w Poznaniu')
            ->assertSee('sprzedaz@example.com');
    }

    /** Tekst wklejany przez admina, ale pokazywany publicznie — bez surowego HTML i niebezpiecznych linków. */
    public function test_markdown_is_rendered_without_raw_html_or_unsafe_links(): void
    {
        AppSetting::current()->fill([
            'shop_description' => "<script>alert(1)</script>\n\n[klik](javascript:alert(2))",
        ])->save();

        $this->get('/')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('javascript:alert(2)', false);
    }
}
