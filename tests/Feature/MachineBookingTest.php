<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Contact;
use App\Models\Item;
use App\Models\MachineBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Przedmioty stacjonarne, moduł "Rezerwacje maszyn" i rola Użytkownik. */
class MachineBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private User $member;

    private Item $printer;

    protected function setUp(): void
    {
        parent::setUp();
        // Poniedziałek rano, żeby "dziś" i tydzień były przewidywalne.
        $this->travelTo(Carbon::parse('2026-11-23 09:00'));
        AppSetting::current()->fill(['module_bookings_enabled' => true])->save();
        $this->staff = User::factory()->create(['role' => 'magazynier', 'name' => 'Tomek']);
        $this->member = User::factory()->create(['role' => 'uzytkownik', 'name' => 'Ola Mazur']);
        $this->printer = $this->item('Drukarka 3D', ['stationary' => true]);
    }

    private function item(string $name, array $attributes = []): Item
    {
        static $no = 0;

        return Item::create($attributes + ['inventory_no' => 'M-'.++$no, 'name' => $name, 'condition' => 'uzywany', 'status' => 'dostepny']);
    }

    private function book(User $user, array $data = [])
    {
        return $this->actingAs($user)->post(route('bookings.store'), $data + [
            'item_id' => $this->printer->id, 'date' => '2026-11-24', 'from' => '10:00', 'to' => '12:00', 'purpose' => 'prywatny',
        ]);
    }

    // ---------- przedmiot stacjonarny ----------

    public function test_stationary_item_cannot_be_loaned_reserved_or_put_in_a_kit(): void
    {
        $contact = Contact::create(['name' => 'Jan']);

        $this->actingAs($this->staff)->post(route('items.loans.store', $this->printer), ['contact_id' => $contact->id])->assertSessionHasErrors('loan');
        $this->actingAs($this->staff)->post(route('items.reservations.store', $this->printer), [
            'contact_id' => $contact->id, 'starts_on' => '2026-11-24', 'ends_on' => '2026-11-25',
        ])->assertSessionHasErrors('reservation');
        $this->actingAs($this->staff)->post(route('loan-groups.store'), ['items' => [$this->printer->id], 'contact_id' => $contact->id])->assertSessionHasErrors('items');

        $this->assertSame('dostepny', $this->printer->fresh()->status);
        $this->assertSame(0, $this->printer->loans()->count());
    }

    public function test_stationary_flag_is_saved_from_the_item_form_and_shown_on_the_item_page(): void
    {
        $item = $this->item('Ploter laserowy');
        $form = ['item_name' => 'Ploter laserowy', 'condition' => 'uzywany', 'status' => 'dostepny'];

        $this->actingAs($this->staff)->put(route('items.update', $item), $form + ['stationary' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue($item->fresh()->stationary);

        $this->actingAs($this->staff)->get(route('items.show', $item))
            ->assertSee(__('Stationary item: it stays in the workshop and isn\'t loaned out.'))
            ->assertSee(__('Machine bookings'))
            ->assertDontSee(route('items.loans.store', $item));

        $this->actingAs($this->staff)->put(route('items.update', $item), $form)->assertSessionHasNoErrors();
        $this->assertFalse($item->fresh()->stationary);
    }

    // ---------- rezerwacje ----------

    public function test_module_is_off_by_default(): void
    {
        AppSetting::current()->fill(['module_bookings_enabled' => false])->save();

        $this->actingAs($this->staff)->get(route('bookings.index'))->assertNotFound();
        $this->book($this->staff)->assertNotFound();
    }

    public function test_booking_is_saved_and_shown_in_the_weekly_calendar(): void
    {
        $this->book($this->member, ['description' => 'Obudowa do robota'])->assertRedirect()->assertSessionHasNoErrors();

        $booking = MachineBooking::firstOrFail();
        $this->assertSame('Ola Mazur', $booking->user_name);
        $this->assertSame('2026-11-24 10:00', $booking->starts_at->format('Y-m-d H:i'));

        $this->actingAs($this->staff)->get(route('bookings.index', ['week' => '2026-11-26']))
            ->assertOk()->assertSee('Drukarka 3D')->assertSee('10:00–12:00')->assertSee('Ola Mazur')->assertSee('Obudowa do robota');
        $this->actingAs($this->staff)->get(route('bookings.index', ['week' => '2026-12-03']))->assertDontSee('10:00–12:00');
    }

    public function test_overlapping_bookings_are_refused_but_touching_ones_are_fine(): void
    {
        $this->book($this->member)->assertSessionHasNoErrors();

        foreach ([['09:00', '10:15'], ['11:45', '13:00'], ['10:30', '11:00'], ['09:00', '13:00']] as [$from, $to]) {
            $this->book($this->staff, ['from' => $from, 'to' => $to])->assertSessionHasErrors('from');
        }
        $this->book($this->staff, ['from' => '12:00', 'to' => '13:00'])->assertSessionHasNoErrors();
        $this->book($this->staff, ['from' => '08:00', 'to' => '10:00'])->assertSessionHasNoErrors();

        // Inna maszyna w tym samym czasie: bez problemu.
        $laser = $this->item('Ploter', ['stationary' => true]);
        $this->book($this->member, ['item_id' => $laser->id])->assertSessionHasNoErrors();

        $this->assertSame(4, MachineBooking::count());
    }

    public function test_times_must_be_sensible(): void
    {
        $this->book($this->member, ['from' => '10:10'])->assertSessionHasErrors('from');
        $this->book($this->member, ['from' => '12:00', 'to' => '11:00'])->assertSessionHasErrors('to');
        $this->book($this->member, ['date' => '2026-11-22'])->assertSessionHasErrors('date');
        // Trwający slot (koniec w przyszłości) da się jeszcze zapisać.
        $this->book($this->member, ['date' => '2026-11-23', 'from' => '08:00', 'to' => '10:00'])->assertSessionHasNoErrors();
    }

    public function test_only_stationary_items_in_stock_can_be_booked(): void
    {
        $drill = $this->item('Wiertarka');
        $retired = $this->item('Stara drukarka', ['stationary' => true, 'status' => 'wycofany']);

        $this->book($this->member, ['item_id' => $drill->id])->assertSessionHasErrors('item_id');
        $this->book($this->member, ['item_id' => $retired->id])->assertSessionHasErrors('item_id');
        $this->assertSame(0, MachineBooking::count());
    }

    // ---------- rola Użytkownik ----------

    public function test_member_cannot_block_a_machine_for_maintenance(): void
    {
        $this->book($this->member, ['purpose' => 'konserwacja'])->assertSessionHasErrors('purpose');
        $this->book($this->staff, ['purpose' => 'konserwacja'])->assertSessionHasNoErrors();
    }

    public function test_member_cancels_only_own_future_bookings_staff_cancels_any(): void
    {
        $this->book($this->member);
        $this->book($this->staff, ['from' => '13:00', 'to' => '14:00']);
        [$own, $staffs] = MachineBooking::orderBy('id')->get()->all();

        $this->actingAs($this->member)->delete(route('bookings.destroy', $staffs))->assertForbidden();
        $this->actingAs($this->member)->delete(route('bookings.destroy', $own))->assertRedirect();
        $this->assertModelMissing($own);

        $this->book($this->member, ['from' => '15:00', 'to' => '16:00']);
        $later = MachineBooking::latest('id')->firstOrFail();
        $this->travelTo(Carbon::parse('2026-11-25 09:00'));
        $this->actingAs($this->member)->delete(route('bookings.destroy', $later))->assertForbidden();
        $this->actingAs($this->staff)->delete(route('bookings.destroy', $later))->assertRedirect();
        $this->assertSame(1, MachineBooking::count());
    }

    public function test_member_lands_on_the_calendar_and_sees_nothing_of_the_warehouse(): void
    {
        $this->actingAs($this->member)->get(route('dashboard'))->assertRedirect(route('bookings.index'));

        $this->actingAs($this->member)->get(route('bookings.index'))->assertOk()
            ->assertSee(__('Machines'))
            ->assertDontSee(route('items.index'))
            ->assertDontSee(route('items.show', $this->printer))
            ->assertDontSee(__('Maintenance').'</option>', false);

        foreach ([route('items.index'), route('items.show', $this->printer), route('scan.show'), route('loans.index'), route('settings.index')] as $url) {
            $this->actingAs($this->member)->get($url)->assertForbidden();
        }
    }

    public function test_member_sees_a_message_when_the_module_is_off(): void
    {
        AppSetting::current()->fill(['module_bookings_enabled' => false])->save();

        $this->actingAs($this->member)->get(route('dashboard'))->assertOk()
            ->assertSee(__('Machine booking is switched off at the moment. Ask the workshop staff if you need a machine.'));
    }

    public function test_admin_can_create_a_member_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Kuba', 'email' => 'kuba@example.com', 'role' => 'uzytkownik',
            'password' => 'Haslo#2026x', 'password_confirmation' => 'Haslo#2026x',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(User::where('email', 'kuba@example.com')->firstOrFail()->isMember());
    }

    public function test_staff_dashboard_lists_todays_bookings(): void
    {
        $this->book($this->member, ['date' => '2026-11-23', 'from' => '14:00', 'to' => '16:00']);

        $this->actingAs($this->staff)->get(route('dashboard'))->assertOk()
            ->assertSee(__('Machines booked today'))->assertSee('14:00–16:00')->assertSee('Ola Mazur');
    }

    public function test_names_and_descriptions_are_escaped(): void
    {
        $evil = '<script>alert("xss")</script>';
        $this->member->forceFill(['name' => $evil])->save();
        // Dziś, żeby trafiła też na Panel ("Dziś na maszynach").
        $this->book($this->member, ['date' => '2026-11-23', 'from' => '14:00', 'to' => '15:00', 'description' => $evil])->assertSessionHasNoErrors();

        foreach ([route('bookings.index', ['week' => '2026-11-24']), route('dashboard'), route('items.show', $this->printer)] as $url) {
            $this->actingAs($this->staff)->get($url)->assertOk()->assertDontSee($evil, false)->assertSee(e($evil), false);
        }
    }
}
