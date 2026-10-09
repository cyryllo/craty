<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanTest extends TestCase
{
    use RefreshDatabase;

    private function item(): Item
    {
        return Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'dostepny',
        ]);
    }

    public function test_borrower_name_is_required(): void
    {
        $item = $this->item();

        $this->actingAs(User::factory()->create(['role' => 'magazynier']))
            ->post(route('items.loans.store', $item), ['borrower_name' => '   '])
            ->assertSessionHasErrors('borrower_name');

        $this->assertSame(0, $item->loans()->count());
        $this->assertSame('dostepny', $item->fresh()->status);
    }

    /** W historii ma być widać KOMU wypożyczono i od kogo wróciło — nie tylko zmianę statusu. */
    public function test_history_shows_who_the_item_was_loaned_to_and_returned_by(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);
        $item = $this->item();

        $this->actingAs($user)->post(route('items.loans.store', $item), [
            'borrower_name' => 'Jan Kowalski', 'due_at' => now()->addWeek()->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->get(route('items.show', $item))
            ->assertSee(__('loaned to'))
            ->assertSee('<b>Jan Kowalski</b>', false)
            ->assertSee(now()->addWeek()->format('d.m.Y'));

        $this->actingAs($user)->post(route('loans.return', $item->loans()->first()));

        $this->actingAs($user)->get(route('items.show', $item))
            ->assertSee(__('returned by'));
        $this->assertSame(1, $item->histories()->where('action', 'returned')->where('old_value', 'Jan Kowalski')->count());
    }
}
