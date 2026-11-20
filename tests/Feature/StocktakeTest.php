<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Loan;
use App\Models\Room;
use App\Models\Stocktake;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Inwentaryzacja: zakres miejsca, skanowanie, raport, "przenieś tutaj". */
class StocktakeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private StorageLocation $rackA;

    private StorageLocation $shelfA1;

    private StorageLocation $rackB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'magazynier']);
        $warehouse = Warehouse::create(['name' => 'Magazyn', 'code' => 'M1']);
        $room = Room::create(['warehouse_id' => $warehouse->id, 'name' => 'Hala', 'code' => 'HAL']);
        $this->rackA = StorageLocation::create(['warehouse_id' => $warehouse->id, 'room_id' => $room->id, 'rack' => 'A']);
        $this->shelfA1 = StorageLocation::create(['warehouse_id' => $warehouse->id, 'room_id' => $room->id, 'rack' => 'A', 'shelf' => '1']);
        $this->rackB = StorageLocation::create(['warehouse_id' => $warehouse->id, 'room_id' => $room->id, 'rack' => 'B']);
    }

    private function item(string $name, StorageLocation $location, array $attributes = []): Item
    {
        static $no = 0;

        return Item::create($attributes + ['inventory_no' => 'S-'.++$no, 'name' => $name, 'storage_location_id' => $location->id, 'condition' => 'uzywany', 'status' => 'dostepny']);
    }

    private function start(StorageLocation $location): Stocktake
    {
        $this->actingAs($this->user)->post(route('stocktakes.store'), ['location_id' => $location->id])->assertRedirect();

        return Stocktake::latest('id')->firstOrFail();
    }

    public function test_full_stocktake_report(): void
    {
        $found = $this->item('Wiertarka', $this->rackA);
        $onShelf = $this->item('Poziomica', $this->shelfA1);
        $missing = $this->item('Laser', $this->shelfA1);
        $stray = $this->item('Szlifierka', $this->rackB);
        $loaned = $this->item('Agregat', $this->rackA);
        Loan::lend($loaned, null, 'Ktoś', $this->user);
        $this->item('Sprzedany', $this->rackA, ['status' => 'sprzedany']);

        // Regał A obejmuje też półkę A1.
        $stocktake = $this->start($this->rackA);
        $this->assertSame('Magazyn › Hala › '.__('Rack').' A', $stocktake->label);
        $this->actingAs($this->user)->get(route('stocktakes.show', $stocktake))->assertSee(__('Expected in this place: :count', ['count' => 4]));

        foreach ([$found, $onShelf] as $item) {
            $this->actingAs($this->user)->postJson(route('stocktakes.scan', $stocktake), ['item_id' => $item->id])->assertJsonPath('in_place', true);
        }
        $this->actingAs($this->user)->postJson(route('stocktakes.scan', $stocktake), ['item_id' => $stray->id])->assertJsonPath('in_place', false);
        // Ten sam skan drugi raz niczego nie dubluje.
        $this->actingAs($this->user)->postJson(route('stocktakes.scan', $stocktake), ['item_id' => $found->id]);
        $this->assertSame(3, $stocktake->scans()->count());

        $this->actingAs($this->user)->post(route('stocktakes.finish', $stocktake))->assertRedirect(route('stocktakes.show', $stocktake));

        $report = $this->actingAs($this->user)->get(route('stocktakes.show', $stocktake))->assertOk();
        $report->assertViewHas('missing', fn ($items) => $items->pluck('id')->all() === [$missing->id]);
        $report->assertViewHas('away', fn ($items) => $items->pluck('id')->all() === [$loaned->id]);
        $report->assertViewHas('misplaced', fn ($scans) => $scans->pluck('item_id')->all() === [$stray->id]);
        $report->assertViewHas('matching', fn ($scans) => $scans->pluck('item_id')->sort()->values()->all() === [$found->id, $onShelf->id]);
        $report->assertDontSee('Sprzedany');

        // Po zakończeniu nie da się już skanować.
        $this->actingAs($this->user)->postJson(route('stocktakes.scan', $stocktake), ['item_id' => $missing->id])->assertNotFound();
    }

    public function test_move_here_fixes_the_location_of_a_stray_item(): void
    {
        $stray = $this->item('Szlifierka', $this->rackB);
        $stocktake = $this->start($this->rackA);
        $this->actingAs($this->user)->postJson(route('stocktakes.scan', $stocktake), ['item_id' => $stray->id]);
        $this->actingAs($this->user)->post(route('stocktakes.finish', $stocktake));

        $this->actingAs($this->user)->post(route('stocktakes.move', [$stocktake, $stray]))->assertSessionHasNoErrors();

        $this->assertSame($this->rackA->id, $stray->fresh()->storage_location_id);
        $this->assertNotNull($stocktake->scans()->first()->moved_at);
        // Drugi raz już nie ma czego przenosić.
        $this->actingAs($this->user)->post(route('stocktakes.move', [$stocktake, $stray]))->assertNotFound();
    }

    public function test_stocktake_can_start_from_the_filtered_item_list(): void
    {
        $this->actingAs($this->user)->get(route('items.index', ['warehouse_id' => $this->rackA->warehouse_id, 'room_id' => $this->rackA->room_id, 'rack' => 'B', 'f' => 1]))
            ->assertSee(__('Count this place'));

        $this->actingAs($this->user)->post(route('stocktakes.store'), [
            'warehouse_id' => $this->rackA->warehouse_id, 'room_id' => $this->rackA->room_id, 'rack' => 'B',
        ])->assertRedirect();

        $this->assertEquals(['warehouse_id' => $this->rackA->warehouse_id, 'room_id' => $this->rackA->room_id, 'rack' => 'B'], Stocktake::firstOrFail()->scope);
    }

    public function test_stocktake_without_a_place_is_refused(): void
    {
        $this->actingAs($this->user)->post(route('stocktakes.store'), [])->assertSessionHasErrors('location_id');
        $this->assertSame(0, Stocktake::count());
    }
}
