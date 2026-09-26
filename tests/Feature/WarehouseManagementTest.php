<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Item;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Zanim magazyn dostawał choć jedną lokalizację (regał/półka/pojemnik),
     * w ogóle nie dało się go wybrać na formularzu przedmiotu — nie każdy
     * chce od razu rozpisywać magazyn aż tak szczegółowo. Od teraz
     * WarehouseController::store() dokłada "bazową" lokalizację (bez
     * rack/shelf/bin) automatycznie, więc magazyn jest wybieralny od razu.
     */
    public function test_creating_a_warehouse_also_creates_a_base_location_usable_on_the_item_form(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($magazynier)->post(route('warehouses.store'), [
            'name' => 'Magazyn główny',
            'code' => 'M1',
        ])->assertRedirect(route('warehouses.index'));

        $warehouse = Warehouse::firstOrFail();
        $location = StorageLocation::where('warehouse_id', $warehouse->id)->firstOrFail();
        $this->assertNull($location->rack);
        $this->assertNull($location->shelf);
        $this->assertNull($location->bin);
        $this->assertSame('M1', $location->code);

        // Bez modułu "Rozszerzony magazyn" wystarczy sama nazwa magazynu...
        $this->actingAs($magazynier)->get(route('items.create'))
            ->assertSee('Magazyn główny')
            ->assertDontSee('whole warehouse, no specific spot');

        // ...z modułem trzeba ją odróżnić od szczegółowych lokalizacji.
        AppSetting::current()->fill(['module_locations_enabled' => true])->save();
        $this->actingAs($magazynier)->get(route('items.create'))
            ->assertSee('Magazyn główny — whole warehouse, no specific spot');
    }

    public function test_rooms_and_locations_are_hidden_when_extended_warehouse_module_is_off(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($magazynier)->get(route('rooms.index'))->assertNotFound();
        $this->actingAs($magazynier)->get(route('storage-locations.index'))->assertNotFound();
        $this->actingAs($magazynier)->get(route('settings.index'))
            ->assertDontSee(route('rooms.index'), false)
            ->assertDontSee(route('storage-locations.index'), false);

        AppSetting::current()->fill(['module_locations_enabled' => true])->save();

        $this->actingAs($magazynier)->get(route('rooms.index'))->assertOk();
        $this->actingAs($magazynier)->get(route('storage-locations.index'))->assertOk();
        $this->actingAs($magazynier)->get(route('settings.index'))
            ->assertSee(route('rooms.index'), false)
            ->assertSee(route('storage-locations.index'), false);
    }

    /**
     * Wyłączenie modułu tylko ukrywa szczegóły — przedmiot zapisany wcześniej
     * na konkretnym regale zachowuje go, także po zwykłym zapisie formularza.
     */
    public function test_item_form_without_module_offers_only_warehouses_but_keeps_an_existing_detailed_location(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $warehouse = Warehouse::create(['name' => 'Magazyn główny', 'code' => 'M1']);
        $base = StorageLocation::create(['warehouse_id' => $warehouse->id]);
        $shelf = StorageLocation::create(['warehouse_id' => $warehouse->id, 'rack' => '3', 'shelf' => '2']);
        $item = Item::create([
            'inventory_no' => 'NAR-M1R3P2-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany',
            'status' => 'dostepny', 'storage_location_id' => $shelf->id,
        ]);

        $this->actingAs($magazynier)->get(route('items.create'))
            ->assertSee('value="'.$base->id.'"', false)
            ->assertDontSee('value="'.$shelf->id.'"', false);

        $this->actingAs($magazynier)->get(route('items.edit', $item))
            ->assertSee('value="'.$shelf->id.'" selected', false);

        $this->actingAs($magazynier)->get(route('items.show', $item))
            ->assertSee('M1-R3-P2');
    }

    public function test_warehouse_with_only_the_base_location_and_no_items_can_be_deleted(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $this->actingAs($magazynier)->post(route('warehouses.store'), ['name' => 'Magazyn główny', 'code' => 'M1']);
        $warehouse = Warehouse::firstOrFail();

        $response = $this->actingAs($magazynier)->delete(route('warehouses.destroy', $warehouse));

        $response->assertRedirect(route('warehouses.index'));
        $this->assertModelMissing($warehouse);
        $this->assertSame(0, StorageLocation::count());
    }

    public function test_warehouse_cannot_be_deleted_while_its_base_location_holds_an_item(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $this->actingAs($magazynier)->post(route('warehouses.store'), ['name' => 'Magazyn główny', 'code' => 'M1']);
        $warehouse = Warehouse::firstOrFail();
        $location = StorageLocation::where('warehouse_id', $warehouse->id)->firstOrFail();
        Item::create([
            'inventory_no' => 'GEN-M1-2026-00001', 'name' => 'Wiertarka', 'condition' => 'nowy',
            'status' => 'dostepny', 'storage_location_id' => $location->id,
        ]);

        $response = $this->actingAs($magazynier)->delete(route('warehouses.destroy', $warehouse));

        $response->assertRedirect();
        $this->assertModelExists($warehouse);
    }
}
