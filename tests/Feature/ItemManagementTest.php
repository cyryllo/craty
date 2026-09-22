<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
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
            'item_name' => 'Wkrętarka akumulatorowa',
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

    /**
     * Regresja z dwóch powiązanych, ale różnych bugów zgłoszonych po kolei:
     * 1) pole HTML nazywa się "item_name", nie "name" — na telefonie zwykłe
     *    <input name="name"> jest przez Chrome rozpoznawane jak "imię i
     *    nazwisko" po samym atrybucie, nie tylko etykiecie, i podpowiada
     *    autouzupełnienie danymi z konta Google (autocomplete="off" samo w
     *    sobie nie wystarczyło).
     * 2) etykieta pola używała współdzielonego klucza __('Name'), który w
     *    lang/pl.json jest przetłumaczony jako "Imię i nazwisko" (bo tego
     *    samego klucza używają formularze użytkownika/profilu) — klasyczna
     *    kolizja klucza między różnymi kontekstami, patrz CLAUDE.md
     *    "Watch for key collisions...". Naprawione osobnym kluczem
     *    __('Item name'), nie tylko zmianą name= atrybutu.
     * Sprawdzamy też, że stary klucz "name" nadal działa przy submitcie
     * (kompatybilność wsteczna w prepareForValidation()).
     */
    public function test_item_form_uses_item_name_field_not_name_to_avoid_browser_autofill(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($magazynier)->get(route('items.create'))
            ->assertSee('name="item_name"', false)
            ->assertDontSee('name="name"', false)
            ->assertSee('Item name');

        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'nowy', 'status' => 'dostepny',
        ]);

        $this->actingAs($magazynier)->put(route('items.update', $item), [
            'name' => 'Powinno zostać zignorowane bez item_name',
            'item_name' => 'Wiertarka udarowa',
            'condition' => 'nowy',
            'status' => 'dostepny',
        ])->assertRedirect();

        $this->assertSame('Wiertarka udarowa', $item->fresh()->name);
    }

    /**
     * Przedmiot dodany bez kategorii/lokalizacji dostaje numer z segmentami
     * GEN/BRAK (InventoryNumberGenerator) — jeśli je uzupełni się dopiero
     * później, numer/QR same się nie przeliczają (ItemController::update()
     * tego nie robi). Przycisk "Odśwież kod QR" na karcie przedmiotu ma to
     * naprawiać na żądanie, usuwając też stary, osierocony plik QR.
     */
    public function test_magazynier_can_regenerate_inventory_number_and_qr_for_an_item(): void
    {
        Storage::fake('public');
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $category = Category::create(['name' => 'Narzędzia', 'code' => 'NAR']);
        $warehouse = Warehouse::create(['name' => 'Magazyn 1', 'code' => 'M1']);
        $location = StorageLocation::create(['warehouse_id' => $warehouse->id, 'rack' => 3, 'shelf' => 2, 'bin' => 1]);

        $item = Item::create([
            'inventory_no' => 'GEN-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'nowy', 'status' => 'dostepny',
            'qr_path' => 'qr/GEN-BRAK-2026-00001.svg',
        ]);
        Storage::disk('public')->put($item->qr_path, 'fake-qr');

        // Kategoria/lokalizacja uzupełnione dopiero po fakcie — symulujemy to
        // bezpośrednio, bo update() i tak nie przelicza numeru/QR.
        $item->forceFill(['category_id' => $category->id, 'storage_location_id' => $location->id])->saveQuietly();

        $response = $this->actingAs($magazynier)->post(route('items.regenerate-qr', $item));

        $response->assertRedirect();
        $item->refresh();
        $this->assertStringStartsWith('NAR-M1R3P2K1-', $item->inventory_no);
        Storage::disk('public')->assertMissing('qr/GEN-BRAK-2026-00001.svg');
        Storage::disk('public')->assertExists($item->qr_path);
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
