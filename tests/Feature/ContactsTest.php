<?php

namespace Tests\Feature;

use App\Http\Controllers\LoanProtocolController;
use App\Models\AppSetting;
use App\Models\Contact;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Moduł Kontakty, wypożyczenia z kontaktami, zwrot ze stanem i protokoły PDF. */
class ContactsTest extends TestCase
{
    use RefreshDatabase;

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

    private function disableContactsModule(): void
    {
        AppSetting::current()->fill(['module_contacts_enabled' => false])->save();
    }

    // ---------- kontakty ----------

    public function test_contact_can_be_created_found_and_edited(): void
    {
        $user = $this->magazynier();

        $this->actingAs($user)->post(route('contacts.store'), [
            'name' => 'Jan Kowalski', 'type' => 'osoba', 'phone' => '600 100 200', 'email' => 'jan@example.com',
        ])->assertRedirect();

        $contact = Contact::firstOrFail();
        $this->assertSame('600 100 200', $contact->phone);

        $this->actingAs($user)->get(route('contacts.index', ['q' => '600 100']))->assertOk()->assertSee('Jan Kowalski');
        $this->actingAs($user)->get(route('contacts.index', ['q' => 'nikt']))->assertOk()->assertDontSee('Jan Kowalski');

        $this->actingAs($user)->put(route('contacts.update', $contact), ['name' => 'Serwis Kowalski', 'type' => 'serwis'])
            ->assertRedirect(route('contacts.show', $contact));
        $this->assertSame('serwis', $contact->fresh()->type);
    }

    public function test_contact_type_defaults_to_person_and_rejects_unknown_types(): void
    {
        $user = $this->magazynier();

        $this->actingAs($user)->post(route('contacts.store'), ['name' => 'Bez typu'])->assertSessionHasNoErrors();
        $this->assertSame('osoba', Contact::firstOrFail()->type);

        $this->actingAs($user)->post(route('contacts.store'), ['name' => 'X', 'type' => 'admin'])->assertSessionHasErrors('type');
    }

    public function test_contact_page_shows_its_loan_history(): void
    {
        $contact = Contact::create(['name' => 'Anna Nowak']);
        $item = $this->item();
        Loan::create(['item_id' => $item->id, 'contact_id' => $contact->id, 'borrower_name' => 'Anna Nowak', 'borrowed_at' => now()]);

        $this->actingAs($this->magazynier())->get(route('contacts.show', $contact))
            ->assertOk()->assertSee('Wiertarka')->assertSee(route('loans.protocol.issue', 1), false);
    }

    public function test_deleting_a_contact_keeps_its_loans_with_the_saved_name(): void
    {
        $contact = Contact::create(['name' => 'Anna Nowak']);
        $loan = Loan::create(['item_id' => $this->item()->id, 'contact_id' => $contact->id, 'borrower_name' => 'Anna Nowak', 'borrowed_at' => now()]);

        $this->actingAs($this->magazynier())->delete(route('contacts.destroy', $contact))->assertRedirect(route('contacts.index'));

        $loan->refresh();
        $this->assertNull($loan->contact_id);
        $this->assertSame('Anna Nowak', $loan->borrowerLabel());
    }

    public function test_contacts_are_hidden_when_the_module_is_off(): void
    {
        $this->disableContactsModule();
        $user = $this->magazynier();

        $this->actingAs($user)->get(route('contacts.index'))->assertNotFound();
        $this->actingAs($user)->get(route('dashboard'))->assertDontSee(route('contacts.index'), false);
    }

    // ---------- wypożyczenie ----------

    public function test_loan_to_an_existing_contact(): void
    {
        $contact = Contact::create(['name' => 'Anna Nowak']);
        $item = $this->item();
        $user = $this->magazynier();

        $this->actingAs($user)->post(route('items.loans.store', $item), ['contact_id' => $contact->id])->assertSessionHasNoErrors();

        $loan = $item->loans()->firstOrFail();
        $this->assertSame($contact->id, $loan->contact_id);
        $this->assertSame('Anna Nowak', $loan->borrower_name);
        $this->assertSame($user->id, $loan->issued_by);
        $this->assertSame('uzywany', $loan->condition_out);
        $this->assertSame('wypozyczony', $item->fresh()->status);
    }

    public function test_loan_to_a_new_contact_creates_it_on_the_fly(): void
    {
        $item = $this->item();

        $this->actingAs($this->magazynier())->post(route('items.loans.store', $item), [
            'contact_id' => 'new', 'new_contact_name' => 'Piotr Wiśniewski', 'new_contact_phone' => '500 500 500',
        ])->assertSessionHasNoErrors();

        $contact = Contact::where('name', 'Piotr Wiśniewski')->firstOrFail();
        $this->assertSame('500 500 500', $contact->phone);
        $this->assertSame($contact->id, $item->loans()->firstOrFail()->contact_id);
    }

    public function test_loan_form_requires_a_contact_and_rejects_unknown_ones(): void
    {
        $item = $this->item();
        $user = $this->magazynier();

        $this->actingAs($user)->post(route('items.loans.store', $item), [])->assertSessionHasErrors('contact_id');
        $this->actingAs($user)->post(route('items.loans.store', $item), ['contact_id' => 'new'])->assertSessionHasErrors('new_contact_name');
        $this->actingAs($user)->post(route('items.loans.store', $item), ['contact_id' => 999])->assertSessionHasErrors('contact_id');

        $this->assertSame(0, $item->loans()->count());
    }

    public function test_without_the_module_the_loan_uses_plain_text(): void
    {
        $this->disableContactsModule();
        $item = $this->item();
        $user = $this->magazynier();

        $this->actingAs($user)->get(route('items.show', $item))->assertSee('name="borrower_name"', false)->assertDontSee('name="contact_id"', false);
        $this->actingAs($user)->post(route('items.loans.store', $item), ['borrower_name' => 'Sąsiad'])->assertSessionHasNoErrors();

        $loan = $item->loans()->firstOrFail();
        $this->assertNull($loan->contact_id);
        $this->assertSame('Sąsiad', $loan->borrowerLabel());
        $this->assertSame(0, Contact::count());
    }

    // ---------- zwrot ----------

    public function test_return_records_condition_and_notes_and_updates_the_item(): void
    {
        $item = $this->item();
        $user = $this->magazynier();
        $loan = Loan::create(['item_id' => $item->id, 'borrower_name' => 'X', 'condition_out' => 'uzywany', 'borrowed_at' => now()]);
        $item->update(['status' => 'wypozyczony']);

        $this->actingAs($user)->post(route('loans.return', $loan), [
            'condition_in' => 'uszkodzony', 'return_notes' => 'Pęknięta obudowa',
        ])->assertSessionHasNoErrors();

        $loan->refresh();
        $this->assertNotNull($loan->returned_at);
        $this->assertSame('uszkodzony', $loan->condition_in);
        $this->assertSame('Pęknięta obudowa', $loan->return_notes);
        $this->assertSame($user->id, $loan->returned_to);
        $this->assertSame('uszkodzony', $item->fresh()->condition);
        $this->assertSame('dostepny', $item->fresh()->status);

        // Drugi zwrot tego samego wypożyczenia nie przechodzi.
        $this->actingAs($user)->post(route('loans.return', $loan))->assertNotFound();
    }

    // ---------- protokoły ----------

    public function test_protocols_are_pdfs_and_the_return_one_needs_a_return(): void
    {
        $item = $this->item();
        $loan = Loan::create(['item_id' => $item->id, 'borrower_name' => 'Anna Nowak', 'condition_out' => 'uzywany', 'borrowed_at' => now()]);
        $user = $this->magazynier();

        $this->actingAs($user)->get(route('loans.protocol.issue', $loan))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($user)->get(route('loans.protocol.return', $loan))->assertNotFound();

        $loan->update(['returned_at' => now(), 'condition_in' => 'uszkodzony']);
        $this->actingAs($user)->get(route('loans.protocol.return', $loan))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_return_protocol_lists_contact_details_and_the_condition_change(): void
    {
        $contact = Contact::create(['name' => 'Anna Nowak', 'phone' => '600 100 200', 'address' => 'ul. Polna 1, Kraków']);
        $item = $this->item(['serial_number' => 'SN-123']);
        $loan = Loan::create([
            'item_id' => $item->id, 'contact_id' => $contact->id, 'borrower_name' => 'Anna Nowak',
            'condition_out' => 'uzywany', 'condition_in' => 'uszkodzony', 'return_notes' => 'Pęknięta obudowa',
            'borrowed_at' => now()->subWeek(), 'returned_at' => now(),
        ]);

        $html = view('loans.protocol', LoanProtocolController::viewData($loan, 'return'))->render();

        foreach (['Anna Nowak', '600 100 200', 'ul. Polna 1, Kraków', 'NAR-BRAK-2026-00001', 'SN-123', 'Pęknięta obudowa', 'changed since issue', $loan->protocolNumber()] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }

    public function test_warehouse_workers_only_can_open_protocols(): void
    {
        $loan = Loan::create(['item_id' => $this->item()->id, 'borrower_name' => 'X', 'borrowed_at' => now()]);

        $this->get(route('loans.protocol.issue', $loan))->assertRedirect(route('login'));
    }

    // ---------- migracja starych wpisów ----------

    public function test_migration_turns_old_borrower_names_into_contacts(): void
    {
        $item = $this->item();
        foreach (['Jan Kowalski', ' jan kowalski ', 'Anna Nowak', ''] as $name) {
            Loan::create(['item_id' => $item->id, 'borrower_name' => $name, 'borrowed_at' => now()]);
        }

        (require database_path('migrations/2026_11_05_120000_d_move_loan_borrower_names_to_contacts.php'))->up();

        $this->assertSame(['Anna Nowak', 'Jan Kowalski'], Contact::orderBy('name')->pluck('name')->all());
        $jan = Contact::where('name', 'Jan Kowalski')->firstOrFail();
        $this->assertSame(2, $jan->loans()->count());
        $this->assertSame(1, Loan::whereNull('contact_id')->count(), 'Pusta nazwa nie staje się kontaktem.');

        // Ponowne uruchomienie niczego nie dubluje.
        (require database_path('migrations/2026_11_05_120000_d_move_loan_borrower_names_to_contacts.php'))->up();
        $this->assertSame(2, Contact::count());
    }
}
