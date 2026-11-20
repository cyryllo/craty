<?php

namespace Tests\Feature\Security;

use App\Http\Controllers\LoanProtocolController;
use App\Models\AppSetting;
use App\Models\Consumable;
use App\Models\Contact;
use App\Models\Item;
use App\Models\Loan;
use App\Models\LoanGroup;
use App\Models\Repair;
use App\Models\Reservation;
use App\Models\Stocktake;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Funkcje z wersji 1.3 i 1.4 (kontakty, serwis, zestawy, rezerwacje,
 * inwentaryzacja, materiały, seria skanów) sprawdzone tak jak reszta w
 * SecurityTest: wstrzykiwanie kodu na każdej nowej stronie i w protokole PDF,
 * działanie na cudzych rekordach i ponowne wykonanie jednorazowych akcji.
 */
class NewFeaturesSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** Klasyczny skrypt i wyjście z atrybutu HTML. */
    private const EVIL = ['<script>alert("xss")</script>', '"><img src=x onerror=alert(1)>'];

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        AppSetting::current()->fill(['module_service_enabled' => true, 'module_consumables_enabled' => true])->save();
        $this->user = User::factory()->create(['role' => 'magazynier']);
    }

    private function evil(): string
    {
        return implode(' ', self::EVIL);
    }

    private function item(array $attributes = []): Item
    {
        static $no = 0;

        return Item::create($attributes + ['inventory_no' => 'X-'.++$no, 'name' => $this->evil(), 'condition' => 'uzywany', 'status' => 'dostepny']);
    }

    private function assertEscaped(string $html, string $where): void
    {
        foreach (self::EVIL as $payload) {
            $this->assertStringNotContainsString($payload, $html, "Nieucieczony HTML na: {$where}");
        }
    }

    private function page(string $url): void
    {
        $this->assertEscaped($this->actingAs($this->user)->get($url)->assertOk()->getContent(), $url);
    }

    // ---------- XSS ----------

    public function test_contacts_kits_reservations_and_service_escape_user_text(): void
    {
        $contact = Contact::create(['name' => $this->evil(), 'phone' => $this->evil(), 'address' => $this->evil(), 'notes' => $this->evil()]);
        $kitItems = [$this->item(), $this->item()];

        $this->actingAs($this->user)->post(route('loan-groups.store'), [
            'items' => collect($kitItems)->pluck('id')->all(),
            'contact_id' => $contact->id,
            'due_at' => today()->toDateString(),
            'notes' => $this->evil(),
        ])->assertSessionHasNoErrors();
        $group = LoanGroup::firstOrFail();

        $reserved = $this->item();
        Reservation::create([
            'item_id' => $reserved->id, 'borrower_name' => $this->evil(), 'notes' => $this->evil(),
            'starts_on' => today(), 'ends_on' => today()->addDay(), 'created_by' => $this->user->id,
        ]);

        // Jedna naprawa w serwisie z Kontaktów, druga w serwisie wpisanym ręcznie.
        $repaired = $this->item();
        $this->actingAs($this->user)->post(route('items.repairs.store', $repaired), [
            'contact_id' => $contact->id, 'fault_description' => $this->evil(), 'sent_at' => today()->subWeek()->toDateString(),
            'expected_at' => today()->subDay()->toDateString(),
        ])->assertSessionHasNoErrors();
        $repairedByName = $this->item();
        $this->actingAs($this->user)->post(route('items.repairs.store', $repairedByName), [
            'service_name' => $this->evil(), 'fault_description' => $this->evil(), 'sent_at' => today()->toDateString(),
        ])->assertSessionHasNoErrors();

        foreach ([
            route('contacts.index'), route('contacts.show', $contact), route('contacts.edit', $contact),
            route('loans.index'), route('loan-groups.show', $group), route('dashboard'),
            route('items.show', $kitItems[0]), route('items.show', $reserved), route('items.show', $repaired), route('items.show', $repairedByName),
            route('service.index'), route('items.index'),
        ] as $url) {
            $this->page($url);
        }

        $this->actingAs($this->user)->post(route('loan-groups.return', $group), ['returned' => $group->loans->pluck('id')->all()]);
        $group->refresh();
        foreach (['issue', 'return'] as $kind) {
            $this->assertEscaped(view('loans.protocol', LoanProtocolController::groupViewData($group, $kind))->render(), "protokół zestawu ({$kind})");
        }
        $loan = Loan::lend($reserved, $contact, $contact->name, $this->user, ['notes' => $this->evil()]);
        $this->assertEscaped(view('loans.protocol', LoanProtocolController::viewData($loan, 'issue'))->render(), 'protokół wydania');
    }

    public function test_error_messages_with_item_names_are_escaped(): void
    {
        $sold = $this->item(['status' => 'sprzedany']);
        $create = route('loan-groups.create', ['items' => [$sold->id]]);

        $response = $this->actingAs($this->user)->from($create)->followingRedirects()
            ->post(route('loan-groups.store'), ['items' => [$sold->id], 'contact_id' => 'new', 'new_contact_name' => 'Jan']);

        $response->assertSee(__('These items can\'t be loaned right now: :items', ['items' => '']));
        $this->assertEscaped($response->getContent(), 'błąd zestawu');
    }

    public function test_old_contact_input_cannot_run_as_alpine_code(): void
    {
        $item = $this->item(['name' => 'Wiertarka']);
        $payload = "'+alert(document.cookie)+'";

        $html = $this->actingAs($this->user)->from(route('items.show', $item))->followingRedirects()
            ->post(route('items.loans.store', $item), ['contact_id' => $payload])->getContent();

        // {{ }} zamieniłoby ' na &#039;, które przeglądarka odkodowuje, zanim
        // Alpine wykona x-data jako JS; @js() koduje cudzysłów jako \u0027.
        $this->assertStringNotContainsString("contact: '&#039;+alert", $html);
        $this->assertStringContainsString("contact: '\\u0027+alert(document.cookie)+\\u0027'", $html);
    }

    public function test_consumables_escape_names_notes_and_movement_notes(): void
    {
        $consumable = Consumable::create(['name' => $this->evil(), 'unit' => 'szt', 'min_quantity' => 10, 'notes' => $this->evil()]);
        $this->actingAs($this->user)->post(route('consumables.adjust', $consumable), ['mode' => 'add', 'amount' => 2, 'note' => $this->evil()])
            ->assertSessionHasNoErrors();

        foreach ([route('consumables.index'), route('consumables.index', ['low' => 1]), route('consumables.show', $consumable), route('consumables.edit', $consumable), route('dashboard')] as $url) {
            $this->page($url);
        }

        $this->assertEscaped($this->actingAs($this->user)->followingRedirects()->from(route('consumables.show', $consumable))
            ->post(route('consumables.adjust', $consumable), ['mode' => 'take', 'amount' => 1])->getContent(), 'komunikat po pobraniu');
    }

    public function test_stocktake_pages_escape_place_and_item_names(): void
    {
        $warehouse = Warehouse::create(['name' => $this->evil(), 'code' => 'M1']);
        $here = StorageLocation::create(['warehouse_id' => $warehouse->id, 'rack' => 'A']);
        $elsewhere = StorageLocation::create(['warehouse_id' => $warehouse->id, 'rack' => 'B']);
        $this->item(['storage_location_id' => $here->id]);
        $stray = $this->item(['storage_location_id' => $elsewhere->id]);

        $this->actingAs($this->user)->post(route('stocktakes.store'), ['location_id' => $here->id]);
        $stocktake = Stocktake::firstOrFail();
        $this->page(route('stocktakes.index'));
        $this->page(route('stocktakes.show', $stocktake));

        $json = $this->actingAs($this->user)->postJson(route('stocktakes.scan', $stocktake), ['item_id' => $stray->id]);
        $json->assertOk()->assertHeader('Content-Type', 'application/json');

        $this->actingAs($this->user)->post(route('stocktakes.finish', $stocktake));
        $this->page(route('stocktakes.show', $stocktake));

        $this->assertEscaped($this->actingAs($this->user)->followingRedirects()->from(route('stocktakes.show', $stocktake))
            ->post(route('stocktakes.move', [$stocktake, $stray]))->getContent(), 'komunikat po przeniesieniu');
    }

    public function test_scan_resolve_answers_json_not_html(): void
    {
        $item = $this->item(['ean' => '5901234123457']);

        $this->actingAs($this->user)->get(route('scan.resolve', ['code' => '5901234123457']))
            ->assertOk()->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('item.name', $this->evil());
        $this->page(route('scan.batch'));
    }

    // ---------- cudze rekordy i akcje jednorazowe ----------

    public function test_kit_return_ignores_loans_from_another_kit_or_single_loans(): void
    {
        [$a, $b, $single] = [$this->item(['name' => 'A']), $this->item(['name' => 'B']), $this->item(['name' => 'C'])];
        $jan = Contact::create(['name' => 'Jan']);
        foreach ([$a, $b] as $item) {
            $this->actingAs($this->user)->post(route('loan-groups.store'), ['items' => [$item->id], 'contact_id' => $jan->id])->assertSessionHasNoErrors();
        }
        [$groupA, $groupB] = LoanGroup::orderBy('id')->get()->all();
        $singleLoan = Loan::lend($single, null, 'Jan', $this->user);

        $this->actingAs($this->user)->post(route('loan-groups.return', $groupA), [
            'returned' => [$groupB->loans->first()->id, $singleLoan->id],
        ]);

        $this->assertNull($groupB->loans->first()->fresh()->returned_at);
        $this->assertNull($singleLoan->fresh()->returned_at);
        $this->assertSame('wypozyczony', $b->fresh()->status);
    }

    public function test_stocktake_move_only_works_for_items_scanned_elsewhere_and_only_once(): void
    {
        $warehouse = Warehouse::create(['name' => 'Magazyn', 'code' => 'M1']);
        $here = StorageLocation::create(['warehouse_id' => $warehouse->id, 'rack' => 'A']);
        $elsewhere = StorageLocation::create(['warehouse_id' => $warehouse->id, 'rack' => 'B']);
        $stray = $this->item(['name' => 'Zabłąkany', 'storage_location_id' => $elsewhere->id]);
        $notScanned = $this->item(['name' => 'Obcy', 'storage_location_id' => $elsewhere->id]);

        $this->actingAs($this->user)->post(route('stocktakes.store'), ['location_id' => $here->id]);
        $stocktake = Stocktake::firstOrFail();
        $this->actingAs($this->user)->postJson(route('stocktakes.scan', $stocktake), ['item_id' => $stray->id])->assertOk();

        $this->actingAs($this->user)->post(route('stocktakes.move', [$stocktake, $notScanned]))->assertNotFound();
        $this->assertSame($elsewhere->id, $notScanned->fresh()->storage_location_id);

        $this->actingAs($this->user)->post(route('stocktakes.move', [$stocktake, $stray]))->assertRedirect();
        $this->actingAs($this->user)->post(route('stocktakes.move', [$stocktake, $stray]))->assertNotFound();
    }

    public function test_finished_stocktake_cannot_be_scanned_or_finished_again(): void
    {
        $warehouse = Warehouse::create(['name' => 'Magazyn', 'code' => 'M1']);
        $here = StorageLocation::create(['warehouse_id' => $warehouse->id, 'rack' => 'A']);
        $item = $this->item(['name' => 'Wiertarka', 'storage_location_id' => $here->id]);

        $this->actingAs($this->user)->post(route('stocktakes.store'), ['location_id' => $here->id]);
        $stocktake = Stocktake::firstOrFail();
        $this->actingAs($this->user)->post(route('stocktakes.finish', $stocktake))->assertRedirect();

        $this->actingAs($this->user)->postJson(route('stocktakes.scan', $stocktake), ['item_id' => $item->id])->assertNotFound();
        $this->actingAs($this->user)->post(route('stocktakes.finish', $stocktake))->assertNotFound();
        $this->assertSame(0, $stocktake->scans()->count());
    }

    public function test_a_reservation_can_be_issued_or_cancelled_only_once(): void
    {
        $item = $this->item(['name' => 'Wiertarka']);
        $reservation = Reservation::create([
            'item_id' => $item->id, 'borrower_name' => 'Jan', 'starts_on' => today(), 'ends_on' => today()->addDay(), 'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)->post(route('reservations.issue', $reservation))->assertRedirect();
        $this->actingAs($this->user)->post(route('reservations.issue', $reservation))->assertNotFound();
        $this->actingAs($this->user)->delete(route('reservations.destroy', $reservation))->assertNotFound();
        $this->assertSame(1, Loan::count());
    }

    public function test_consumable_quantity_cannot_go_negative_or_be_set_through_the_form(): void
    {
        $consumable = Consumable::create(['name' => 'Śrubki', 'unit' => 'szt']);
        $consumable->adjust(5, null, $this->user);

        foreach ([['mode' => 'take', 'amount' => -10], ['mode' => 'add', 'amount' => 0], ['mode' => 'set', 'amount' => -1], ['mode' => 'drop', 'amount' => 1], ['mode' => 'add', 'amount' => '1e20']] as $input) {
            $this->actingAs($this->user)->post(route('consumables.adjust', $consumable), $input)->assertSessionHasErrors();
        }
        $this->actingAs($this->user)->post(route('consumables.adjust', $consumable), ['mode' => 'take', 'amount' => 50]);
        $this->assertEquals(0, $consumable->fresh()->quantity);

        $this->actingAs($this->user)->put(route('consumables.update', $consumable), ['name' => 'Śrubki', 'unit' => 'szt', 'quantity' => 999]);
        $this->assertEquals(0, $consumable->fresh()->quantity);
    }

    public function test_disabled_modules_hide_every_route_not_just_the_menu(): void
    {
        $contact = Contact::create(['name' => 'Jan']);
        $consumable = Consumable::create(['name' => 'Śrubki', 'unit' => 'szt']);
        $item = $this->item(['name' => 'Wiertarka']);
        AppSetting::current()->fill(['module_contacts_enabled' => false, 'module_service_enabled' => false, 'module_consumables_enabled' => false])->save();

        foreach ([route('contacts.index'), route('contacts.show', $contact), route('consumables.index'), route('consumables.show', $consumable), route('service.index')] as $url) {
            $this->actingAs($this->user)->get($url)->assertNotFound();
        }
        $this->actingAs($this->user)->post(route('consumables.adjust', $consumable), ['mode' => 'add', 'amount' => 1])->assertNotFound();
        $this->actingAs($this->user)->delete(route('contacts.destroy', $contact))->assertNotFound();
        $this->actingAs($this->user)->post(route('items.repairs.store', $item), ['fault_description' => 'x'])->assertNotFound();
        $this->assertModelExists($contact);
        $this->assertSame(0, Repair::count());
    }
}
