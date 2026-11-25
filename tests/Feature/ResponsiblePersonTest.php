<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Contact;
use App\Models\Item;
use App\Models\ItemHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Osoba odpowiedzialna za przedmiot (właściciel albo opiekun, np. maszyny w
 * makerspace), wybierana z Kontaktów.
 */
class ResponsiblePersonTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Contact $jan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'magazynier']);
        $this->jan = Contact::create(['name' => 'Jan Kowalski', 'type' => 'osoba', 'phone' => '600 100 200']);
    }

    public function test_item_form_saves_the_person_shown_on_item_and_contact_pages_and_in_history(): void
    {
        $item = $this->item();
        $this->actingAs($this->user)->get(route('items.edit', $item))->assertOk()
            ->assertSee('name="responsible_contact_id"', false)->assertSee('Jan Kowalski');

        $this->update($item, ['responsible_contact_id' => $this->jan->id, 'responsible_role' => 'wlasciciel'])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame([$this->jan->id, 'wlasciciel'], [$item->responsible_contact_id, $item->responsible_role]);
        $this->actingAs($this->user)->get(route('items.show', $item))
            ->assertSee(__('Owner'))->assertSee('Jan Kowalski')->assertSee('tel:600 100 200', false)
            ->assertSee(__('Owner').': Jan Kowalski');
        $this->actingAs($this->user)->get(route('contacts.show', $this->jan))
            ->assertSee(__('Owner or caretaker of'))->assertSee('Ploter laserowy');
        $this->assertSame(__('Owner').': Jan Kowalski', ItemHistory::where('action', 'responsible_changed')->value('new_value'));
    }

    public function test_clearing_the_person_clears_the_role_and_invalid_values_are_rejected(): void
    {
        $item = $this->item(['responsible_contact_id' => $this->jan->id, 'responsible_role' => 'opiekun']);

        $this->update($item, ['responsible_contact_id' => '', 'responsible_role' => 'opiekun'])->assertSessionHasNoErrors();
        $this->assertNull($item->fresh()->responsible_contact_id);
        $this->assertNull($item->fresh()->responsible_role);

        $this->update($item, ['responsible_contact_id' => 999, 'responsible_role' => 'opiekun'])->assertSessionHasErrors('responsible_contact_id');
        $this->update($item, ['responsible_contact_id' => $this->jan->id, 'responsible_role' => 'szef'])->assertSessionHasErrors('responsible_role');
    }

    public function test_with_contacts_module_off_the_field_is_hidden_and_saving_keeps_it(): void
    {
        $item = $this->item(['responsible_contact_id' => $this->jan->id, 'responsible_role' => 'opiekun']);
        AppSetting::current()->fill(['module_contacts_enabled' => false])->save();

        $this->actingAs($this->user)->get(route('items.edit', $item))->assertDontSee('name="responsible_contact_id"', false);
        $this->update($item, [])->assertSessionHasNoErrors();

        $this->assertSame($this->jan->id, $item->fresh()->responsible_contact_id);
        // Na karcie przedmiotu nadal widać osobę, tylko bez linku do wyłączonych Kontaktów.
        $this->actingAs($this->user)->get(route('items.show', $item))->assertSee('Jan Kowalski')->assertDontSee(route('contacts.show', $this->jan));
    }

    public function test_deleting_the_contact_leaves_the_item_without_a_person(): void
    {
        $item = $this->item(['responsible_contact_id' => $this->jan->id, 'responsible_role' => 'opiekun']);

        $this->jan->delete();

        $this->assertNull($item->fresh()->responsible_contact_id);
        $this->assertNull($item->fresh()->responsibleRoleLabel());
        $this->actingAs($this->user)->get(route('items.show', $item))->assertOk()->assertDontSee('data-responsible', false);
    }

    public function test_machine_booking_grid_shows_the_caretaker_to_members(): void
    {
        AppSetting::current()->fill(['module_bookings_enabled' => true])->save();
        $this->item(['stationary' => true, 'responsible_contact_id' => $this->jan->id, 'responsible_role' => 'opiekun']);
        $member = User::factory()->create(['role' => 'uzytkownik']);

        $this->actingAs($member)->get(route('bookings.index'))->assertOk()->assertSee(__('Caretaker').': Jan Kowalski');
    }

    public function test_csv_import_sets_the_person_by_contact_name_and_export_round_trips(): void
    {
        $item = $this->item();
        $csv = "nr_ewidencyjny;nazwa;osoba_odpowiedzialna;rola_osoby\n{$item->inventory_no};;jan kowalski;\n;Nowa frezarka;Nieznany Ktoś;\n;Drukarka;Jan Kowalski;właściciel\n";

        $this->actingAs($this->user)->post(route('data-transfer.items.preview'), ['file' => UploadedFile::fake()->createWithContent('a.csv', $csv)])
            ->assertOk()
            ->assertSee(__('There is no contact named ":name". Add them in Contacts first.', ['name' => 'Nieznany Ktoś']))
            ->assertSee(__('Errors: :count', ['count' => 1]));
        $this->actingAs($this->user)->post(route('data-transfer.items.apply'))->assertOk();

        $this->assertSame([$this->jan->id, 'opiekun'], [$item->fresh()->responsible_contact_id, $item->fresh()->responsible_role]);
        $printer = Item::where('name', 'Drukarka')->firstOrFail();
        $this->assertSame('wlasciciel', $printer->responsible_role);
        $this->assertFalse(Item::where('name', 'Nowa frezarka')->exists());

        $export = $this->actingAs($this->user)->get(route('items.export'))->streamedContent();
        $this->assertStringContainsString(';"Jan Kowalski";opiekun;', $export);
        $this->actingAs($this->user)->post(route('data-transfer.items.preview'), ['file' => UploadedFile::fake()->createWithContent('b.csv', $export)])
            ->assertSee(__('Changed: :count', ['count' => 0]));
    }

    private function item(array $attributes = []): Item
    {
        return Item::create($attributes + ['name' => 'Ploter laserowy', 'inventory_no' => 'MAS-'.uniqid(), 'condition' => 'uzywany', 'status' => 'dostepny']);
    }

    private function update(Item $item, array $data)
    {
        return $this->actingAs($this->user)->put(route('items.update', $item), $data + ['name' => $item->name, 'condition' => 'uzywany', 'status' => 'dostepny']);
    }
}
