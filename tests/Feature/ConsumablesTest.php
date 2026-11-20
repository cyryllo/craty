<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Consumable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Moduł Materiały eksploatacyjne: ilości, ruchy, ostrzeżenie o niskim stanie. */
class ConsumablesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        AppSetting::current()->fill(['module_consumables_enabled' => true])->save();
        $this->user = User::factory()->create(['role' => 'magazynier']);
    }

    private function consumable(): Consumable
    {
        $this->actingAs($this->user)->post(route('consumables.store'), [
            'name' => 'Wkręty 4x40', 'unit' => 'szt', 'min_quantity' => 50, 'quantity' => 200,
        ])->assertSessionHasNoErrors();

        return Consumable::firstOrFail();
    }

    public function test_module_is_off_by_default_and_hidden_then(): void
    {
        AppSetting::query()->delete();
        $this->assertFalse(\App\Support\Modules::isEnabled('consumables'));

        $this->actingAs($this->user)->get(route('consumables.index'))->assertNotFound();
    }

    public function test_initial_stock_is_recorded_as_a_movement(): void
    {
        $consumable = $this->consumable();

        $this->assertSame('200.000', $consumable->quantity);
        $this->assertSame(1, $consumable->movements()->count());
        $this->actingAs($this->user)->get(route('consumables.show', $consumable))->assertSee(__('Initial stock'));
    }

    public function test_take_restock_and_set_counted_stock(): void
    {
        $consumable = $this->consumable();
        $adjust = fn (array $data) => $this->actingAs($this->user)->post(route('consumables.adjust', $consumable), $data);

        $adjust(['mode' => 'take', 'amount' => 30, 'note' => 'Montaż szafki'])->assertSessionHasNoErrors();
        $this->assertSame('170.000', $consumable->fresh()->quantity);

        $adjust(['mode' => 'add', 'amount' => 2.5])->assertSessionHasNoErrors();
        $this->assertSame('172.500', $consumable->fresh()->quantity);

        $adjust(['mode' => 'set', 'amount' => 160])->assertSessionHasNoErrors();
        $this->assertSame('160.000', $consumable->fresh()->quantity);
        $this->assertSame('-12.500', $consumable->movements()->first()->change, 'Korekta zapisuje różnicę.');

        // Pobranie więcej, niż jest: stan zatrzymuje się na zerze.
        $adjust(['mode' => 'take', 'amount' => 999])->assertSessionHasNoErrors();
        $this->assertSame('0.000', $consumable->fresh()->quantity);

        $adjust(['mode' => 'take', 'amount' => 0])->assertSessionHasErrors('amount');
        $adjust(['mode' => 'steal', 'amount' => 1])->assertSessionHasErrors('mode');
    }

    public function test_editing_never_changes_the_quantity(): void
    {
        $consumable = $this->consumable();

        $this->actingAs($this->user)->put(route('consumables.update', $consumable), [
            'name' => 'Wkręty 4x40 ocynk', 'unit' => 'szt', 'quantity' => 5,
        ])->assertSessionHasNoErrors();

        $this->assertSame('200.000', $consumable->fresh()->quantity);
        $this->assertSame('Wkręty 4x40 ocynk', $consumable->fresh()->name);
    }

    public function test_low_stock_shows_on_the_list_filter_and_dashboard(): void
    {
        $consumable = $this->consumable();
        Consumable::create(['name' => 'Klej', 'unit' => 'l', 'min_quantity' => 1])->adjust(5, null, $this->user);
        $consumable->adjust(-160, null, $this->user); // 40 < minimum 50

        $this->actingAs($this->user)->get(route('consumables.index', ['low' => 1]))->assertSee('Wkręty 4x40')->assertDontSee('Klej');
        $this->actingAs($this->user)->get(route('dashboard'))->assertSee(__('Consumables running low'))->assertSee('Wkręty 4x40');
    }

    public function test_quantity_formatting(): void
    {
        $this->assertSame('12', Consumable::formatQuantity('12.000'));
        $this->assertSame('2,5', Consumable::formatQuantity(2.5));
        $this->assertSame('0', Consumable::formatQuantity(0));
        $this->assertSame('1 000', Consumable::formatQuantity(1000));
        $this->assertSame('-12,5', Consumable::formatQuantity(-12.5));
    }
}
