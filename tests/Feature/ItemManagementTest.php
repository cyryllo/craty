<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_magazynier_can_add_an_item_and_gets_an_inventory_number_with_qr(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $category = Category::create(['name' => 'Narzędzia', 'code' => 'NAR']);

        $response = $this->actingAs($magazynier)->post('/items', [
            'name' => 'Wkrętarka akumulatorowa',
            'condition' => 'nowy',
            'status' => 'dostepny',
            'category_id' => $category->id,
        ]);

        $item = Item::firstOrFail();

        $response->assertRedirect(route('items.show', $item));
        $this->assertStringStartsWith('NAR-', $item->inventory_no);
        $this->assertNotNull($item->qr_path);
        $this->assertDatabaseHas('item_histories', ['item_id' => $item->id, 'action' => 'created']);
    }

    public function test_viewer_role_cannot_create_items(): void
    {
        $viewer = User::factory()->create(['role' => 'podglad']);

        $this->actingAs($viewer)->get('/items/create')->assertForbidden();

        $this->actingAs($viewer)->post('/items', [
            'name' => 'Próba dodania',
            'condition' => 'nowy',
            'status' => 'dostepny',
        ])->assertForbidden();

        $this->assertDatabaseCount('items', 0);
    }

    public function test_viewer_can_see_items_but_not_categories(): void
    {
        $viewer = User::factory()->create(['role' => 'podglad']);

        $this->actingAs($viewer)->get('/items')->assertOk();
        $this->actingAs($viewer)->get('/categories')->assertForbidden();
    }
}
