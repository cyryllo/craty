<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Contact;
use App\Models\Item;
use App\Models\Loan;
use App\Models\Repair;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Moduł Serwis: naprawy, przeglądy okresowe, gwarancje, przypomnienia. */
class ServiceModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AppSetting::current()->fill(['module_service_enabled' => true])->save();
    }

    private function magazynier(): User
    {
        return User::factory()->create(['role' => 'magazynier']);
    }

    private function item(array $attributes = []): Item
    {
        return Item::create($attributes + [
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'dostepny',
        ]);
    }

    // ---------- moduł ----------

    public function test_module_is_off_by_default(): void
    {
        AppSetting::query()->delete();

        $this->assertFalse(\App\Support\Modules::isEnabled('service'));
    }

    public function test_everything_is_hidden_when_the_module_is_off(): void
    {
        AppSetting::current()->fill(['module_service_enabled' => false])->save();
        $item = $this->item();
        $user = $this->magazynier();

        $this->actingAs($user)->get(route('service.index'))->assertNotFound();
        $this->actingAs($user)->post(route('items.repairs.store', $item), ['fault_description' => 'X', 'sent_at' => today()->toDateString()])->assertNotFound();
        $this->actingAs($user)->post(route('items.inspections.store', $item), ['performed_at' => today()->toDateString(), 'passed' => 1])->assertNotFound();
        $this->actingAs($user)->get(route('items.show', $item))->assertDontSee(route('items.repairs.store', $item), false);
        $this->actingAs($user)->get(route('items.edit', $item))->assertDontSee('name="warranty_until"', false);
    }

    public function test_saving_the_item_form_with_the_module_off_keeps_service_dates(): void
    {
        $item = $this->item(['warranty_until' => '2027-01-31', 'inspection_interval_months' => 6]);
        AppSetting::current()->fill(['module_service_enabled' => false])->save();

        $this->actingAs($this->magazynier())->put(route('items.update', $item), [
            'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'dostepny', 'warranty_until' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame('2027-01-31', $item->fresh()->warranty_until->toDateString());
        $this->assertSame(6, $item->fresh()->inspection_interval_months);
    }

    // ---------- naprawy ----------

    public function test_sending_for_repair_and_completing_it(): void
    {
        $contact = Contact::create(['name' => 'Serwis Bosch', 'type' => 'serwis']);
        $item = $this->item(['status' => 'do_sprzedazy']);
        $user = $this->magazynier();

        $this->actingAs($user)->post(route('items.repairs.store', $item), [
            'contact_id' => $contact->id, 'fault_description' => 'Nie włącza się',
            'sent_at' => today()->toDateString(), 'expected_at' => today()->addWeek()->toDateString(),
        ])->assertSessionHasNoErrors();

        $repair = Repair::firstOrFail();
        $this->assertSame('Serwis Bosch', $repair->service_name);
        $this->assertSame('w_naprawie', $item->fresh()->status);
        $this->actingAs($user)->get(route('service.index'))->assertSee('Wiertarka')->assertSee('Serwis Bosch');

        $this->actingAs($user)->post(route('repairs.complete', $repair), [
            'returned_at' => today()->toDateString(), 'cost' => '120.50', 'work_done' => 'Wymiana szczotek', 'condition' => 'nowy',
        ])->assertSessionHasNoErrors();

        $repair->refresh();
        $this->assertSame('120.50', $repair->cost);
        $this->assertSame($user->id, $repair->closed_by);
        $this->assertSame('do_sprzedazy', $item->fresh()->status, 'Wraca status sprzed naprawy.');
        $this->assertSame('nowy', $item->fresh()->condition);

        // Drugie zakończenie tej samej naprawy nie przechodzi.
        $this->actingAs($user)->post(route('repairs.complete', $repair), ['returned_at' => today()->toDateString()])->assertNotFound();

        // Historia serwisowa na karcie przedmiotu i naprawy na karcie serwisu.
        $this->actingAs($user)->get(route('items.show', $item))->assertSee('Wymiana szczotek');
        $this->actingAs($user)->get(route('contacts.show', $contact))->assertSee('Nie włącza się');
    }

    public function test_repair_in_own_workshop_without_a_service(): void
    {
        $item = $this->item();

        $this->actingAs($this->magazynier())->post(route('items.repairs.store', $item), [
            'fault_description' => 'Luźny kabel', 'sent_at' => today()->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->assertSame(__('In-house'), Repair::firstOrFail()->serviceLabel());
    }

    public function test_cannot_send_a_loaned_item_or_loan_an_item_under_repair(): void
    {
        $user = $this->magazynier();
        $loaned = $this->item(['status' => 'wypozyczony']);
        Loan::create(['item_id' => $loaned->id, 'borrower_name' => 'X', 'borrowed_at' => now()]);

        $this->actingAs($user)->post(route('items.repairs.store', $loaned), ['fault_description' => 'X', 'sent_at' => today()->toDateString()])
            ->assertSessionHasErrors('repair');
        $this->assertSame(0, Repair::count());

        $repaired = $this->item(['inventory_no' => 'NAR-BRAK-2026-00002']);
        $this->actingAs($user)->post(route('items.repairs.store', $repaired), ['fault_description' => 'X', 'sent_at' => today()->toDateString()]);
        $this->actingAs($user)->post(route('items.loans.store', $repaired), ['contact_id' => 'new', 'new_contact_name' => 'Y'])
            ->assertSessionHasErrors('loan');
        $this->assertSame(0, $repaired->loans()->count());

        // Druga naprawa tego samego przedmiotu też nie.
        $this->actingAs($user)->post(route('items.repairs.store', $repaired), ['fault_description' => 'X', 'sent_at' => today()->toDateString()])
            ->assertSessionHasErrors('repair');
    }

    public function test_repair_needs_a_fault_description_and_sane_dates(): void
    {
        $item = $this->item();
        $user = $this->magazynier();

        $this->actingAs($user)->post(route('items.repairs.store', $item), ['sent_at' => today()->toDateString()])->assertSessionHasErrors('fault_description');
        $this->actingAs($user)->post(route('items.repairs.store', $item), [
            'fault_description' => 'X', 'sent_at' => today()->toDateString(), 'expected_at' => today()->subDay()->toDateString(),
        ])->assertSessionHasErrors('expected_at');
    }

    // ---------- przeglądy ----------

    public function test_recording_an_inspection_schedules_the_next_one_from_the_interval(): void
    {
        $item = $this->item(['inspection_interval_months' => 6, 'next_inspection_at' => today()->subDay()]);

        $this->actingAs($this->magazynier())->post(route('items.inspections.store', $item), [
            'performed_at' => '2026-08-31', 'passed' => '0', 'inspection_notes' => 'Uszkodzona izolacja',
        ])->assertSessionHasNoErrors();

        $inspection = $item->inspections()->firstOrFail();
        $this->assertFalse($inspection->passed);
        $this->assertSame('Uszkodzona izolacja', $inspection->notes);
        // 31 sierpnia + 6 miesięcy = koniec lutego, bez przeskoku na marzec.
        $this->assertSame('2027-02-28', $item->fresh()->next_inspection_at->toDateString());
    }

    public function test_inspection_without_an_interval_clears_the_next_date(): void
    {
        $item = $this->item(['next_inspection_at' => today()]);

        $this->actingAs($this->magazynier())->post(route('items.inspections.store', $item), [
            'performed_at' => today()->toDateString(), 'passed' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertNull($item->fresh()->next_inspection_at);
    }

    public function test_inspection_date_cannot_be_in_the_future(): void
    {
        $this->actingAs($this->magazynier())->post(route('items.inspections.store', $this->item()), [
            'performed_at' => today()->addDay()->toDateString(), 'passed' => '1',
        ])->assertSessionHasErrors('performed_at');
    }

    // ---------- gwarancje i przypomnienia ----------

    public function test_item_form_saves_warranty_and_inspection_settings(): void
    {
        $item = $this->item();

        $this->actingAs($this->magazynier())->put(route('items.update', $item), [
            'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'dostepny',
            'warranty_until' => '2028-05-01', 'inspection_interval_months' => 12, 'next_inspection_at' => '2027-01-15',
        ])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame('2028-05-01', $item->warranty_until->toDateString());
        $this->assertSame(12, $item->inspection_interval_months);
        $this->assertSame('2027-01-15', $item->next_inspection_at->toDateString());
    }

    public function test_dashboard_and_service_page_warn_about_inspections_and_warranties(): void
    {
        $this->item(['name' => 'Gaśnica', 'next_inspection_at' => today()->subDays(3)]);
        $this->item(['inventory_no' => 'NAR-BRAK-2026-00002', 'name' => 'Laptop', 'warranty_until' => today()->addDays(10)]);
        $this->item(['inventory_no' => 'NAR-BRAK-2026-00003', 'name' => 'Daleki termin', 'warranty_until' => today()->addYear(), 'next_inspection_at' => today()->addYear()]);
        $this->item(['inventory_no' => 'NAR-BRAK-2026-00004', 'name' => 'Sprzedana gaśnica', 'status' => 'sprzedany', 'next_inspection_at' => today()->subDay()]);
        $user = $this->magazynier();

        $this->actingAs($user)->get(route('service.index'))
            ->assertSee('Gaśnica')->assertSee('Laptop')
            ->assertDontSee('Daleki termin')->assertDontSee('Sprzedana gaśnica');

        // Na pulpicie te same listy w skrócie (sam pulpit pokazuje też ostatnio
        // dodane przedmioty, więc tu sprawdzamy tylko, że sekcja jest).
        $this->actingAs($user)->get(route('dashboard'))
            ->assertSee(__('Service: needs attention'))->assertSee(route('service.index'), false);
    }

    public function test_item_states_for_warranty_and_inspection(): void
    {
        $this->assertSame('expired', $this->item(['warranty_until' => today()->subDay()])->warrantyState());
        $this->assertSame('soon', (new Item(['warranty_until' => today()->addDays(5)]))->warrantyState());
        $this->assertNull((new Item(['warranty_until' => today()->addYear()]))->warrantyState());
        $this->assertSame('overdue', (new Item(['next_inspection_at' => today()->subDay()]))->inspectionState());
        $this->assertSame('soon', (new Item(['next_inspection_at' => today()]))->inspectionState());
    }
}
