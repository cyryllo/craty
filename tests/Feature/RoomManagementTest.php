<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Room;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tak samo jak magazyn dostaje bazową lokalizację przy tworzeniu
     * (WarehouseController::store()), pomieszczenie ma być wybieralne od
     * razu, bez konieczności dopisywania regału/półki/pojemnika — nie
     * każdy chce rozpisywać magazyn aż tak szczegółowo.
     */
    public function test_creating_a_room_also_creates_a_base_location_usable_on_the_item_form(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $warehouse = Warehouse::create(['name' => 'Magazyn główny', 'code' => 'M1']);

        $this->actingAs($magazynier)->post(route('rooms.store'), [
            'warehouse_id' => $warehouse->id,
            'name' => 'Hala produkcyjna',
            'code' => 'hala1',
        ])->assertRedirect(route('rooms.index'));

        $room = Room::firstOrFail();
        $this->assertSame('HALA1', $room->code);

        $location = StorageLocation::where('room_id', $room->id)->firstOrFail();
        $this->assertSame($warehouse->id, $location->warehouse_id);
        $this->assertNull($location->rack);
        $this->assertSame('M1-HALA1', $location->code);

        $this->actingAs($magazynier)->get(route('items.create'))
            ->assertSee('Magazyn główny — M1-HALA1');
    }

    public function test_room_code_must_be_unique_within_its_warehouse_but_not_globally(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $warehouseOne = Warehouse::create(['name' => 'Magazyn 1', 'code' => 'M1']);
        $warehouseTwo = Warehouse::create(['name' => 'Magazyn 2', 'code' => 'M2']);
        Room::create(['warehouse_id' => $warehouseOne->id, 'name' => 'Hala', 'code' => 'HALA1']);

        $duplicate = $this->actingAs($magazynier)->post(route('rooms.store'), [
            'warehouse_id' => $warehouseOne->id, 'name' => 'Inna hala', 'code' => 'HALA1',
        ]);
        $duplicate->assertSessionHasErrors('code');

        $sameCodeOtherWarehouse = $this->actingAs($magazynier)->post(route('rooms.store'), [
            'warehouse_id' => $warehouseTwo->id, 'name' => 'Hala', 'code' => 'HALA1',
        ]);
        $sameCodeOtherWarehouse->assertSessionHasNoErrors();
        $this->assertSame(2, Room::count());
    }

    /**
     * storage_locations.warehouse_id jest zdenormalizowane z rooms.warehouse_id
     * — pozwolenie na zmianę magazynu pomieszczenia po fakcie rozjechałoby te
     * dwa pola na już istniejących lokalizacjach, więc formularz edycji nawet
     * nie wysyła warehouse_id (patrz RoomController::validated()).
     */
    public function test_room_warehouse_cannot_be_changed_after_creation(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $warehouseOne = Warehouse::create(['name' => 'Magazyn 1', 'code' => 'M1']);
        $warehouseTwo = Warehouse::create(['name' => 'Magazyn 2', 'code' => 'M2']);
        $room = Room::create(['warehouse_id' => $warehouseOne->id, 'name' => 'Hala', 'code' => 'HALA1']);

        $this->actingAs($magazynier)->put(route('rooms.update', $room), [
            'warehouse_id' => $warehouseTwo->id,
            'name' => 'Hala zaktualizowana',
            'code' => 'HALA1',
        ])->assertRedirect();

        $room->refresh();
        $this->assertSame($warehouseOne->id, $room->warehouse_id);
        $this->assertSame('Hala zaktualizowana', $room->name);
    }

    public function test_room_with_only_the_base_location_and_no_items_can_be_deleted(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $warehouse = Warehouse::create(['name' => 'Magazyn główny', 'code' => 'M1']);
        $this->actingAs($magazynier)->post(route('rooms.store'), [
            'warehouse_id' => $warehouse->id, 'name' => 'Hala', 'code' => 'HALA1',
        ]);
        $room = Room::firstOrFail();

        $response = $this->actingAs($magazynier)->delete(route('rooms.destroy', $room));

        $response->assertRedirect(route('rooms.index'));
        $this->assertModelMissing($room);
        $this->assertSame(0, StorageLocation::count());
    }

    public function test_room_cannot_be_deleted_while_its_base_location_holds_an_item(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $warehouse = Warehouse::create(['name' => 'Magazyn główny', 'code' => 'M1']);
        $this->actingAs($magazynier)->post(route('rooms.store'), [
            'warehouse_id' => $warehouse->id, 'name' => 'Hala', 'code' => 'HALA1',
        ]);
        $room = Room::firstOrFail();
        $location = StorageLocation::where('room_id', $room->id)->firstOrFail();
        Item::create([
            'inventory_no' => 'GEN-M1-HALA1-2026-00001', 'name' => 'Wiertarka', 'condition' => 'nowy',
            'status' => 'dostepny', 'storage_location_id' => $location->id,
        ]);

        $response = $this->actingAs($magazynier)->delete(route('rooms.destroy', $room));

        $response->assertRedirect();
        $this->assertModelExists($room);
    }

    /** Formularz lokalizacji grupuje pokoje po magazynie (optgroup), ale serwer i tak dopilnowuje spójności. */
    public function test_storage_location_rejects_a_room_from_a_different_warehouse(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $warehouseOne = Warehouse::create(['name' => 'Magazyn 1', 'code' => 'M1']);
        $warehouseTwo = Warehouse::create(['name' => 'Magazyn 2', 'code' => 'M2']);
        $roomInWarehouseTwo = Room::create(['warehouse_id' => $warehouseTwo->id, 'name' => 'Hala', 'code' => 'HALA1']);

        $response = $this->actingAs($magazynier)->post(route('storage-locations.store'), [
            'warehouse_id' => $warehouseOne->id,
            'room_id' => $roomInWarehouseTwo->id,
            'rack' => '3',
        ]);

        $response->assertSessionHasErrors('room_id');
    }
}
