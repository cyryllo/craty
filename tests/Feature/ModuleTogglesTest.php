<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Item;
use App\Models\User;
use App\Support\Modules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleTogglesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_module_is_enabled_by_default(): void
    {
        $this->assertTrue(Modules::isEnabled('sales'));

        $user = User::factory()->create(['role' => 'magazynier']);
        $this->actingAs($user)->get(route('sale-listings.index'))->assertOk();
    }

    public function test_disabling_the_sales_module_hides_its_routes(): void
    {
        AppSetting::current()->fill(['module_sales_enabled' => false])->save();
        $user = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka',
            'condition' => 'nowy', 'status' => 'dostepny',
        ]);

        $this->actingAs($user)->get(route('sale-listings.index'))->assertNotFound();
        $this->actingAs($user)->get(route('sale-listings.exported'))->assertNotFound();
        $this->actingAs($user)->get(route('items.sale-listing.create', $item))->assertNotFound();
    }

    /** Decyzja: wyłączenie modułu Sprzedaż chowa też pchli targ, niezależnie od jego własnego przełącznika. */
    public function test_disabling_the_sales_module_also_hides_the_flea_market(): void
    {
        AppSetting::current()->fill([
            'public_marketplace_enabled' => true,
            'module_sales_enabled' => false,
        ])->save();

        $this->get(route('marketplace.index'))->assertNotFound();
    }

    public function test_disabling_the_sales_module_hides_the_nav_link(): void
    {
        AppSetting::current()->fill(['module_sales_enabled' => false])->save();
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('sale-listings.index'), false);
    }

    public function test_disabling_the_sales_module_hides_the_sale_card_on_item_show(): void
    {
        AppSetting::current()->fill(['module_sales_enabled' => false])->save();
        $user = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00002', 'name' => 'Śrubokręt',
            'condition' => 'nowy', 'status' => 'dostepny',
        ]);

        $this->actingAs($user)->get(route('items.show', $item))
            ->assertOk()
            ->assertDontSee(__('Prepare sale listing'));
    }

    public function test_disabling_the_sales_module_hides_the_dashboard_tile(): void
    {
        AppSetting::current()->fill(['module_sales_enabled' => false])->save();
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('sale-listings.exported'), false);
    }

    public function test_admin_can_disable_a_module_from_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('settings.modules.update'), [
            'module_sales_enabled' => '0',
        ]);

        $response->assertRedirect();
        $this->assertFalse(AppSetting::current()->module_sales_enabled);
    }

    public function test_admin_can_re_enable_a_module_from_settings(): void
    {
        AppSetting::current()->fill(['module_sales_enabled' => false])->save();
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('settings.modules.update'), [
            'module_sales_enabled' => '1',
        ]);

        $response->assertRedirect();
        $this->assertTrue(AppSetting::current()->module_sales_enabled);
    }
}
