<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    public function test_items_can_be_found_by_serial_number_or_ean(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wkrętarka', 'condition' => 'nowy',
            'status' => 'dostepny', 'serial_number' => 'SN-998877', 'ean' => '5901234123457',
        ]);
        Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00002', 'name' => 'Inny przedmiot', 'condition' => 'nowy',
            'status' => 'dostepny',
        ]);

        $bySerial = $this->actingAs($magazynier)->get('/items?q=998877');
        $bySerial->assertSee('Wkrętarka')->assertDontSee('Inny przedmiot');

        $byEan = $this->actingAs($magazynier)->get('/items?q=5901234123457');
        $byEan->assertSee('Wkrętarka')->assertDontSee('Inny przedmiot');
    }

    public function test_magazynier_can_remove_a_single_photo_and_another_one_becomes_primary(): void
    {
        Storage::fake('public');
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'nowy', 'status' => 'dostepny',
        ]);
        $first = $item->photos()->create(['path' => 'items/1/a.jpg', 'is_primary' => true, 'sort_order' => 0]);
        $second = $item->photos()->create(['path' => 'items/1/b.jpg', 'is_primary' => false, 'sort_order' => 1]);
        Storage::disk('public')->put($first->path, 'fake');
        Storage::disk('public')->put($second->path, 'fake');

        $response = $this->actingAs($magazynier)->delete(route('items.photos.destroy', [$item, $first]));

        $response->assertRedirect();
        $this->assertModelMissing($first);
        Storage::disk('public')->assertMissing($first->path);
        $this->assertTrue($second->fresh()->is_primary);
    }

    public function test_magazynier_can_remove_a_single_attachment(): void
    {
        Storage::fake('public');
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'nowy', 'status' => 'dostepny',
        ]);
        $attachment = $item->attachments()->create(['path' => 'items/1/attachments/faktura.pdf', 'label' => 'Faktura']);
        Storage::disk('public')->put($attachment->path, 'fake');

        $response = $this->actingAs($magazynier)->delete(route('items.attachments.destroy', [$item, $attachment]));

        $response->assertRedirect();
        $this->assertModelMissing($attachment);
        Storage::disk('public')->assertMissing($attachment->path);
    }

    public function test_cannot_delete_a_photo_belonging_to_a_different_item(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $itemOne = Item::create(['inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'A', 'condition' => 'nowy', 'status' => 'dostepny']);
        $itemTwo = Item::create(['inventory_no' => 'NAR-BRAK-2026-00002', 'name' => 'B', 'condition' => 'nowy', 'status' => 'dostepny']);
        $photo = $itemOne->photos()->create(['path' => 'items/1/a.jpg', 'is_primary' => true, 'sort_order' => 0]);

        $this->actingAs($magazynier)->delete(route('items.photos.destroy', [$itemTwo, $photo]))->assertNotFound();
        $this->assertModelExists($photo);
    }
}
