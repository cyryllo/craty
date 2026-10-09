<?php

namespace Tests\Feature;

use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageLocationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_rack_shelf_bin_combination_in_the_same_warehouse_is_rejected_with_validation(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $warehouse = Warehouse::create(['name' => 'Magazyn główny', 'code' => 'M1']);
        StorageLocation::create(['warehouse_id' => $warehouse->id, 'rack' => '3', 'shelf' => '2']);

        $response = $this->actingAs($magazynier)->post(route('storage-locations.store'), [
            'warehouse_id' => $warehouse->id,
            'rack' => '3',
            'shelf' => '2',
        ]);

        // Komunikat ma wskazywać istniejącą lokalizację (po kodzie) i tłumaczyć,
        // że jedna lokalizacja i tak może trzymać kilka przedmiotów naraz —
        // realne zgłoszenie użytkownika, który nie wiedział o tym i próbował
        // założyć duplikat zamiast wybrać istniejącą na formularzu przedmiotu.
        $response->assertSessionHasErrors('combination');
        $this->assertStringContainsString('M1-R3-P2', session('errors')->first('combination'));
        $this->assertSame(1, StorageLocation::count());
    }

    public function test_same_combination_in_a_different_warehouse_is_allowed(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $warehouseOne = Warehouse::create(['name' => 'Magazyn 1', 'code' => 'M1']);
        $warehouseTwo = Warehouse::create(['name' => 'Magazyn 2', 'code' => 'M2']);
        StorageLocation::create(['warehouse_id' => $warehouseOne->id, 'rack' => '3', 'shelf' => '2']);

        $response = $this->actingAs($magazynier)->post(route('storage-locations.store'), [
            'warehouse_id' => $warehouseTwo->id,
            'rack' => '3',
            'shelf' => '2',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(2, StorageLocation::count());
    }

    public function test_updating_a_location_without_changing_its_combination_is_allowed(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $warehouse = Warehouse::create(['name' => 'Magazyn główny', 'code' => 'M1']);
        $location = StorageLocation::create(['warehouse_id' => $warehouse->id, 'rack' => '3', 'shelf' => '2']);

        $response = $this->actingAs($magazynier)->put(route('storage-locations.update', $location), [
            'warehouse_id' => $warehouse->id,
            'rack' => '3',
            'shelf' => '2',
            'note' => 'zaktualizowana notatka',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('zaktualizowana notatka', $location->fresh()->note);
    }
}
