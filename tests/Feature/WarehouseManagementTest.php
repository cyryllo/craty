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
        ])->assertRedirect(route('warehouse-structure.index'));

        $warehouse = Warehouse::firstOrFail();
        $location = StorageLocation::where('warehouse_id', $warehouse->id)->firstOrFail();
        $this->assertNull($location->rack);
        $this->assertNull($location->shelf);
        $this->assertNull($location->bin);
        $this->assertSame('M1', $location->code);

        $this->actingAs($magazynier)->get(route('items.create'))
            ->assertSee('Magazyn główny — whole warehouse, no specific spot');
    }

    /** Magazyny, pomieszczenia i lokalizacje żyją teraz w jednym drzewie — dawne listy tylko przekierowują. */
    public function test_old_flat_lists_redirect_to_the_warehouse_structure(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($magazynier)->get(route('warehouse-structure.index'))->assertOk();
        foreach (['warehouses.index', 'rooms.index', 'storage-locations.index'] as $route) {
            $this->actingAs($magazynier)->get(route($route))->assertRedirect(route('warehouse-structure.index'));
        }
        $this->actingAs($magazynier)->get(route('settings.index'))
            ->assertSee(route('warehouse-structure.index'), false)
            ->assertDontSee(route('warehouses.index'), false);
    }

    public function test_deleting_a_detailed_location_moves_its_items_one_level_up(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $warehouse = Warehouse::create(['name' => 'Magazyn główny', 'code' => 'M1']);
        $base = StorageLocation::create(['warehouse_id' => $warehouse->id]);
        $shelf = StorageLocation::create(['warehouse_id' => $warehouse->id, 'rack' => '3', 'shelf' => '2']);
        $item = Item::create([
            'inventory_no' => 'NAR-M1R3P2-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany',
            'status' => 'dostepny', 'storage_location_id' => $shelf->id,
        ]);

        $this->actingAs($magazynier)->delete(route('storage-locations.destroy', $shelf))
            ->assertRedirect(route('warehouse-structure.index'));

        $this->assertModelMissing($shelf);
        $this->assertSame($base->id, $item->fresh()->storage_location_id);

        // Samego "całego magazynu" nie da się usunąć osobno.
        $this->actingAs($magazynier)->delete(route('storage-locations.destroy', $base))->assertRedirect();
        $this->assertModelExists($base);
    }

    public function test_warehouse_with_only_the_base_location_and_no_items_can_be_deleted(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $this->actingAs($magazynier)->post(route('warehouses.store'), ['name' => 'Magazyn główny', 'code' => 'M1']);
        $warehouse = Warehouse::firstOrFail();

        $response = $this->actingAs($magazynier)->delete(route('warehouses.destroy', $warehouse));

        $response->assertRedirect(route('warehouse-structure.index'));
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
