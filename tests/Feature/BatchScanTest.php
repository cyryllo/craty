<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Seria skanów: rozpoznawanie kodów po stronie serwera (/scan/resolve). */
class BatchScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_own_qr_urls_manufacturer_codes_and_inventory_numbers(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create(['inventory_no' => 'NAR-M1-2026-00042', 'name' => 'Wiertarka', 'ean' => '5901234123457', 'serial_number' => 'SN-9', 'condition' => 'uzywany', 'status' => 'dostepny']);

        foreach ([route('items.show', $item), 'https://inna-domena.pl/items/'.$item->id, '5901234123457', 'SN-9', 'NAR-M1-2026-00042'] as $code) {
            $this->actingAs($user)->getJson(route('scan.resolve', ['code' => $code]))
                ->assertOk()->assertJsonPath('found', true)->assertJsonPath('item.id', $item->id)->assertJsonPath('item.unavailable', null);
        }

        $this->actingAs($user)->getJson(route('scan.resolve', ['code' => 'nie-ma']))->assertJsonPath('found', false);
        $this->actingAs($user)->getJson(route('scan.resolve', ['code' => '']))->assertJsonPath('found', false);
    }

    public function test_batch_page_renders_with_kit_and_label_actions(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'magazynier']))->get(route('scan.batch'))
            ->assertOk()->assertSee('data-mode="batch"', false)
            ->assertSee(route('loan-groups.create'), false)->assertSee(route('items.labels.print'), false);
    }
}
