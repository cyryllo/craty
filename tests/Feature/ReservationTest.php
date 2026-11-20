<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Item;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Rezerwacje: zapis, kolizje terminów, ostrzeżenie przy wypożyczeniu, wydanie z rezerwacji. */
class ReservationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'magazynier']);
        $this->item = Item::create(['inventory_no' => 'R-1', 'name' => 'Agregat', 'condition' => 'uzywany', 'status' => 'dostepny']);
    }

    private function reserve(array $data)
    {
        return $this->actingAs($this->user)->post(route('items.reservations.store', $this->item), $data);
    }

    public function test_reservation_is_saved_and_shown(): void
    {
        $contact = Contact::create(['name' => 'Anna Nowak']);

        $this->reserve([
            'contact_id' => $contact->id, 'starts_on' => today()->addDays(3)->toDateString(), 'ends_on' => today()->addDays(5)->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->user)->get(route('items.show', $this->item))->assertSee('Anna Nowak');
        $this->actingAs($this->user)->get(route('loans.index'))->assertSee('Agregat');
    }

    public function test_overlapping_reservations_are_refused(): void
    {
        $this->reserve(['contact_id' => 'new', 'new_contact_name' => 'Pierwszy', 'starts_on' => today()->addDays(3)->toDateString(), 'ends_on' => today()->addDays(6)->toDateString()]);

        $this->reserve(['contact_id' => 'new', 'new_contact_name' => 'Drugi', 'starts_on' => today()->addDays(6)->toDateString(), 'ends_on' => today()->addDays(8)->toDateString()])
            ->assertSessionHasErrors('starts_on');
        // Dzień po końcu pierwszej już wolny.
        $this->reserve(['contact_id' => 'new', 'new_contact_name' => 'Trzeci', 'starts_on' => today()->addDays(7)->toDateString(), 'ends_on' => today()->addDays(8)->toDateString()])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Reservation::count());
    }

    public function test_dates_must_be_in_order_and_not_in_the_past(): void
    {
        $this->reserve(['contact_id' => 'new', 'new_contact_name' => 'X', 'starts_on' => today()->subDay()->toDateString(), 'ends_on' => today()->toDateString()])
            ->assertSessionHasErrors('starts_on');
        $this->reserve(['contact_id' => 'new', 'new_contact_name' => 'X', 'starts_on' => today()->addDays(3)->toDateString(), 'ends_on' => today()->addDay()->toDateString()])
            ->assertSessionHasErrors('ends_on');
    }

    public function test_current_reservation_warns_on_the_loan_form_but_does_not_block(): void
    {
        Reservation::create(['item_id' => $this->item->id, 'borrower_name' => 'Anna Nowak', 'starts_on' => today(), 'ends_on' => today()->addDays(2)]);

        $this->actingAs($this->user)->get(route('items.show', $this->item))
            ->assertSee(__('Reserved for :name until :date.', ['name' => 'Anna Nowak', 'date' => today()->addDays(2)->format('d.m.Y')]));

        $this->actingAs($this->user)->post(route('items.loans.store', $this->item), ['contact_id' => 'new', 'new_contact_name' => 'Ktoś inny'])
            ->assertSessionHasNoErrors();
        $this->assertSame('wypozyczony', $this->item->fresh()->status);
    }

    public function test_issuing_a_reservation_creates_the_loan(): void
    {
        $contact = Contact::create(['name' => 'Anna Nowak']);
        $reservation = Reservation::create(['item_id' => $this->item->id, 'contact_id' => $contact->id, 'borrower_name' => 'Anna Nowak', 'starts_on' => today(), 'ends_on' => today()->addDays(2)]);

        $this->actingAs($this->user)->post(route('reservations.issue', $reservation))->assertRedirect(route('items.show', $this->item));

        $loan = $this->item->loans()->firstOrFail();
        $this->assertSame($contact->id, $loan->contact_id);
        $this->assertSame(today()->addDays(2)->toDateString(), $loan->due_at->toDateString());
        $this->assertSame($loan->id, $reservation->fresh()->loan_id);
        $this->assertSame(0, Reservation::active()->count(), 'Wydana rezerwacja nie jest już aktywna.');

        $this->actingAs($this->user)->post(route('reservations.issue', $reservation))->assertNotFound();
    }

    public function test_cancelling_removes_the_reservation(): void
    {
        $reservation = Reservation::create(['item_id' => $this->item->id, 'borrower_name' => 'X', 'starts_on' => today()->addDay(), 'ends_on' => today()->addDays(2)]);

        $this->actingAs($this->user)->delete(route('reservations.destroy', $reservation))->assertSessionHasNoErrors();

        $this->assertSame(0, Reservation::count());
    }

    public function test_dashboard_lists_reservations_to_hand_out_today_and_tomorrow(): void
    {
        Reservation::create(['item_id' => $this->item->id, 'borrower_name' => 'Jutrzejszy', 'starts_on' => today()->addDay(), 'ends_on' => today()->addDays(2)]);
        $other = Item::create(['inventory_no' => 'R-2', 'name' => 'Laser', 'condition' => 'uzywany', 'status' => 'dostepny']);
        Reservation::create(['item_id' => $other->id, 'borrower_name' => 'Za tydzień', 'starts_on' => today()->addWeek(), 'ends_on' => today()->addWeeks(2)]);

        $this->actingAs($this->user)->get(route('dashboard'))->assertSee('Jutrzejszy')->assertDontSee('Za tydzień');
    }
}
