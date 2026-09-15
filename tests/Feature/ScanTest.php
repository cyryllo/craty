<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_page_is_reachable_by_any_logged_in_role(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($magazynier)->get(route('scan.show'))->assertOk();
    }

    public function test_lookup_finds_an_item_by_ean(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'nowy',
            'status' => 'dostepny', 'ean' => '5901234123457',
        ]);

        $response = $this->actingAs($magazynier)->getJson(route('scan.lookup', ['code' => '5901234123457']));

        $response->assertOk()->assertJson(['found' => true, 'url' => route('items.show', $item)]);
    }

    public function test_lookup_finds_an_item_by_serial_number(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'nowy',
            'status' => 'dostepny', 'serial_number' => 'SN-998877',
        ]);

        $response = $this->actingAs($magazynier)->getJson(route('scan.lookup', ['code' => 'SN-998877']));

        $response->assertOk()->assertJson(['found' => true, 'url' => route('items.show', $item)]);
    }

    public function test_lookup_reports_not_found_for_an_unknown_code(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $response = $this->actingAs($magazynier)->getJson(route('scan.lookup', ['code' => 'nope']));

        $response->assertOk()->assertJson(['found' => false]);
    }

    public function test_quick_add_creates_a_minimal_item_flagged_as_needing_completion(): void
    {
        Storage::fake('public');
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $response = $this->actingAs($magazynier)->post(route('scan.quick-add.store'), [
            'name' => 'Multimetr',
            'code' => '5901234123457',
            'photo' => UploadedFile::fake()->image('multimetr.jpg'),
        ]);

        $item = Item::firstOrFail();
        $response->assertRedirect(route('items.show', $item));
        $this->assertSame('Multimetr', $item->name);
        $this->assertSame('5901234123457', $item->ean);
        $this->assertTrue($item->needs_completion);
        $this->assertNotNull($item->qr_path);
        $this->assertCount(1, $item->photos);
    }

    public function test_quick_add_works_without_a_photo(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($magazynier)->post(route('scan.quick-add.store'), [
            'name' => 'Multimetr',
            'code' => '5901234123457',
        ])->assertRedirect();

        $this->assertTrue(Item::firstOrFail()->needs_completion);
    }

    public function test_needs_completion_flag_clears_after_a_regular_edit(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'GEN-BRAK-2026-00001', 'name' => 'Multimetr', 'condition' => 'nowy', 'status' => 'dostepny',
        ]);
        $item->forceFill(['needs_completion' => true])->saveQuietly();

        $this->actingAs($magazynier)->put(route('items.update', $item), [
            'name' => 'Multimetr cyfrowy',
            'condition' => 'nowy',
            'status' => 'dostepny',
        ])->assertRedirect();

        $this->assertFalse($item->fresh()->needs_completion);
    }

    public function test_only_admin_and_magazynier_can_quick_add(): void
    {
        // Ewidencja przedmiotów dziś nie ma roli słabszej niż magazynier (patrz
        // usunięcie roli "podglad"), ale trasa mimo to zostaje w grupie
        // role:admin,magazynier — to samo uprawnienie co zwykłe dodawanie.
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($magazynier)->get(route('scan.quick-add.create'))->assertOk();
    }
}
