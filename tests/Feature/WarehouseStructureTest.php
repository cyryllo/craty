<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Item;
use App\Models\Room;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseStructureTest extends TestCase
{
    use RefreshDatabase;

    private User $magazynier;

    private Warehouse $warehouse;

    private Room $hall;

    protected function setUp(): void
    {
        parent::setUp();

        $this->magazynier = User::factory()->create(['role' => 'magazynier']);

        $this->warehouse = Warehouse::create(['name' => 'Pracownia', 'code' => 'M1']);
        StorageLocation::create(['warehouse_id' => $this->warehouse->id]);
        $this->hall = Room::create(['warehouse_id' => $this->warehouse->id, 'name' => 'Hala główna', 'code' => 'HALA1']);
        StorageLocation::create(['warehouse_id' => $this->warehouse->id, 'room_id' => $this->hall->id]);
    }

    private function item(string $name, StorageLocation $location, string $status = 'dostepny'): Item
    {
        static $no = 0;

        return Item::create([
            'inventory_no' => 'NAR-M1-2026-'.str_pad((string) ++$no, 5, '0', STR_PAD_LEFT),
            'name' => $name, 'condition' => 'uzywany', 'status' => $status,
            'storage_location_id' => $location->id,
        ]);
    }

    public function test_tree_shows_warehouse_room_rack_shelf_and_bins_with_aggregated_in_stock_counts(): void
    {
        $shelf = StorageLocation::create(['warehouse_id' => $this->warehouse->id, 'room_id' => $this->hall->id, 'rack' => '3', 'shelf' => '2']);
        $bin = StorageLocation::create(['warehouse_id' => $this->warehouse->id, 'room_id' => $this->hall->id, 'rack' => '3', 'shelf' => '2', 'bin' => '1']);
        $loose = StorageLocation::create(['warehouse_id' => $this->warehouse->id, 'rack' => '9']);

        $this->item('Wiertarka', $shelf);
        $this->item('Wkręty', $bin);
        $this->item('Sprzedana piła', $bin, 'sprzedany');
        $this->item('Pilarka', $loose);

        $response = $this->actingAs($this->magazynier)->get(route('warehouse-structure.index'));

        $response->assertOk()
            ->assertSee('Pracownia')
            ->assertSee('Hala główna')
            ->assertSee(__('Rack').' 3')
            ->assertSee(__('Shelf').' 2')
            ->assertSee('K1')
            ->assertSee(__('No room'))
            ->assertSee(__('Rack').' 9')
            // Regał 3 liczy półkę i pojemnik, bez sprzedanego przedmiotu.
            ->assertSee('href="'.e(route('items.index', ['warehouse_id' => $this->warehouse->id, 'room_id' => $this->hall->id, 'rack' => '3'])).'"', false)
            ->assertSee('href="'.e(route('items.index', ['storage_location_id' => $bin->id])).'"', false)
            ->assertDontSee('<x-', false);

        $tree = $response->viewData('tree');
        $this->assertSame(3, $tree[0]['count']);
        $this->assertSame(2, $tree[0]['rooms'][0]['count']);
        $this->assertSame(2, $tree[0]['rooms'][0]['racks'][0]['count']);
        $this->assertSame(1, $tree[0]['looseRacks'][0]['count']);
    }

    public function test_items_list_can_be_filtered_by_a_branch_of_the_tree(): void
    {
        $shelf = StorageLocation::create(['warehouse_id' => $this->warehouse->id, 'room_id' => $this->hall->id, 'rack' => '3', 'shelf' => '2']);
        $bin = StorageLocation::create(['warehouse_id' => $this->warehouse->id, 'room_id' => $this->hall->id, 'rack' => '3', 'shelf' => '2', 'bin' => '1']);
        $otherRack = StorageLocation::create(['warehouse_id' => $this->warehouse->id, 'room_id' => $this->hall->id, 'rack' => '7']);

        $this->item('Wiertarka na półce', $shelf);
        $this->item('Wkręty w pojemniku', $bin);
        $this->item('Klucze na regale siedem', $otherRack);

        $this->actingAs($this->magazynier)
            ->get(route('items.index', ['view' => 'list', 'warehouse_id' => $this->warehouse->id, 'room_id' => $this->hall->id, 'rack' => '3']))
            ->assertSee('Wiertarka na półce')
            ->assertSee('Wkręty w pojemniku')
            ->assertDontSee('Klucze na regale siedem')
            ->assertSee('Pracownia › Hala główna › '.__('Rack').' 3');

        $this->actingAs($this->magazynier)
            ->get(route('items.index', ['view' => 'list', 'storage_location_id' => $bin->id]))
            ->assertSee('Wkręty w pojemniku')
            ->assertDontSee('Wiertarka na półce');
    }

    public function test_hover_lists_item_names_and_filtered_list_links_back_to_the_structure(): void
    {
        $bin = StorageLocation::create(['warehouse_id' => $this->warehouse->id, 'room_id' => $this->hall->id, 'rack' => '3', 'shelf' => '2', 'bin' => '1', 'note' => 'drobnica']);
        $this->item('Wkręty 4x40', $bin);
        $this->item('Sprzedane nity', $bin, 'sprzedany');

        $this->actingAs($this->magazynier)->get(route('warehouse-structure.index'))
            ->assertSee('role="tooltip"', false)
            ->assertSee('• Wkręty 4x40', false)
            ->assertSee('drobnica')
            ->assertDontSee('Sprzedane nity');

        $this->actingAs($this->magazynier)->get(route('items.index', ['storage_location_id' => $bin->id]))
            ->assertSee('href="'.route('warehouse-structure.index').'#warehouse-'.$this->warehouse->id.'"', false);
    }

    public function test_add_buttons_open_prefilled_forms(): void
    {
        $this->actingAs($this->magazynier)
            ->get(route('storage-locations.create', ['warehouse_id' => $this->warehouse->id, 'room_id' => $this->hall->id, 'rack' => '3', 'shelf' => '2']))
            ->assertOk()
            ->assertSee('<option value="'.$this->warehouse->id.'" selected', false)
            ->assertSee('<option value="'.$this->hall->id.'" selected', false)
            ->assertSee('value="3"', false)
            ->assertSee('value="2"', false);

        $this->actingAs($this->magazynier)
            ->get(route('rooms.create', ['warehouse_id' => $this->warehouse->id]))
            ->assertSee('<option value="'.$this->warehouse->id.'" selected', false);
    }

    public function test_saving_a_location_returns_to_the_structure_page(): void
    {
        $this->actingAs($this->magazynier)
            ->post(route('storage-locations.store'), ['warehouse_id' => $this->warehouse->id, 'room_id' => $this->hall->id, 'rack' => '4'])
            ->assertRedirect(route('warehouse-structure.index'));
    }
}
