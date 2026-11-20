<?php

namespace Tests\Feature;

use App\Http\Controllers\LoanProtocolController;
use App\Models\Contact;
use App\Models\Item;
use App\Models\Loan;
use App\Models\LoanGroup;
use App\Models\Repair;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Zestawy: kilka przedmiotów dla jednej osoby, jeden protokół, zwrot całości albo części. */
class KitLoanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'magazynier']);
    }

    private function item(string $name, array $attributes = []): Item
    {
        static $no = 0;

        return Item::create($attributes + ['inventory_no' => 'K-'.++$no, 'name' => $name, 'condition' => 'uzywany', 'status' => 'dostepny']);
    }

    private function kit(): array
    {
        $contact = Contact::create(['name' => 'Ekipa Nowaka', 'phone' => '600 100 200']);
        $items = [$this->item('Wiertarka'), $this->item('Poziomica'), $this->item('Przedłużacz')];

        $this->actingAs($this->user)->post(route('loan-groups.store'), [
            'items' => collect($items)->pluck('id')->all(),
            'contact_id' => $contact->id,
            'due_at' => today()->addWeek()->toDateString(),
            'notes' => 'Wyjazd na budowę',
        ])->assertSessionHasNoErrors();

        return [LoanGroup::firstOrFail(), $items, $contact];
    }

    public function test_kit_loans_every_item_to_the_same_person(): void
    {
        [$group, $items] = $this->kit();

        $this->assertSame(3, $group->loans()->count());
        foreach ($items as $item) {
            $this->assertSame('wypozyczony', $item->fresh()->status);
            $this->assertSame($group->id, $item->loans()->first()->loan_group_id);
            $this->assertSame('Ekipa Nowaka', $item->loans()->first()->borrowerLabel());
        }
        $this->actingAs($this->user)->get(route('loan-groups.show', $group))->assertOk()->assertSee('Poziomica');
        $this->actingAs($this->user)->get(route('loans.index'))->assertSee($group->protocolNumber());
    }

    public function test_create_page_shows_items_from_the_scan_and_flags_unavailable_ones(): void
    {
        $free = $this->item('Wolna');
        $repaired = $this->item('W naprawie', ['status' => 'w_naprawie']);
        Repair::create(['item_id' => $repaired->id, 'fault_description' => 'X', 'sent_at' => today()]);

        $this->actingAs($this->user)->get(route('loan-groups.create', ['items' => [$free->id, $repaired->id]]))
            ->assertOk()->assertSee('Wolna')->assertSee(__('under repair'))
            ->assertSee('name="items[]" value="'.$free->id.'"', false)
            ->assertDontSee('name="items[]" value="'.$repaired->id.'"', false);
    }

    public function test_kit_with_an_unavailable_item_is_refused_as_a_whole(): void
    {
        $free = $this->item('Wolna');
        $loaned = $this->item('Wypożyczona');
        Loan::lend($loaned, null, 'Ktoś', $this->user);

        $this->actingAs($this->user)->post(route('loan-groups.store'), [
            'items' => [$free->id, $loaned->id], 'contact_id' => 'new', 'new_contact_name' => 'X',
        ])->assertSessionHasErrors('items');

        $this->assertSame(0, LoanGroup::count());
        $this->assertSame('dostepny', $free->fresh()->status);
    }

    public function test_partial_return_and_the_rest_later_from_the_item_page(): void
    {
        [$group, [$drill, $level, $cable]] = $this->kit();
        $loans = $group->loans->keyBy('item_id');

        $this->actingAs($this->user)->post(route('loan-groups.return', $group), [
            'returned' => [$loans[$drill->id]->id, $loans[$level->id]->id],
            'condition' => [$loans[$drill->id]->id => 'uszkodzony'],
            'notes' => [$loans[$drill->id]->id => 'Pęknięty uchwyt'],
        ])->assertSessionHasNoErrors();

        $this->assertSame('uszkodzony', $drill->fresh()->condition);
        $this->assertSame('dostepny', $level->fresh()->status);
        $this->assertSame('wypozyczony', $cable->fresh()->status, 'Niezaznaczony przedmiot zostaje wypożyczony.');

        // Reszta zwykłym zwrotem z karty przedmiotu.
        $this->actingAs($this->user)->post(route('loans.return', $loans[$cable->id]))->assertSessionHasNoErrors();
        $this->assertTrue($group->fresh()->load('loans')->isFullyReturned());
    }

    public function test_kit_protocols_list_every_item(): void
    {
        [$group, $items] = $this->kit();
        $loans = $group->loans;
        $loans[0]->registerReturn($this->user, 'uszkodzony', 'Rysa');

        $issue = view('loans.protocol', LoanProtocolController::groupViewData($group->fresh(), 'issue'))->render();
        foreach (['Wiertarka', 'Poziomica', 'Przedłużacz', 'Ekipa Nowaka', '600 100 200', 'Wyjazd na budowę', $group->protocolNumber()] as $text) {
            $this->assertStringContainsString($text, $issue);
        }

        // Protokół zwrotu obejmuje tylko to, co już wróciło.
        $return = view('loans.protocol', LoanProtocolController::groupViewData($group->fresh(), 'return'))->render();
        $this->assertStringContainsString('Wiertarka', $return);
        $this->assertStringContainsString('Rysa', $return);
        $this->assertStringNotContainsString('Przedłużacz', $return);

        $this->actingAs($this->user)->get(route('loan-groups.protocol.issue', $group))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($this->user)->get(route('loan-groups.protocol.return', $group))->assertOk();
    }

    public function test_item_page_of_a_kit_item_links_to_the_kit_and_its_protocol(): void
    {
        [$group, [$drill]] = $this->kit();

        $this->actingAs($this->user)->get(route('items.show', $drill))
            ->assertSee(route('loan-groups.show', $group), false)
            ->assertSee(route('loan-groups.protocol.issue', $group), false);
    }
}
