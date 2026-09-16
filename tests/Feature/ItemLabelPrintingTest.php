<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Trzy szablony etykiet do wyboru (życzenie użytkownika, 2026-09-16), patrz
 * App\Support\ItemLabelTemplates — 32×20mm (QR + nazwa), 35×25mm (sam QR),
 * 50×30mm (QR + nazwa + numer ewidencyjny), każdy z opcjonalną ceną.
 * Wybór dzieje się PO otwarciu podglądu (toolbar na stronie), nie przed —
 * życzenie użytkownika: "niech da możliwość przy podglądzie do wyboru i
 * wyklikania i dopiero potem drukuj".
 */
class ItemLabelPrintingTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_template_shows_qr_and_name_but_not_inventory_number(): void
    {
        $item = $this->makeItem('Wiertarka');
        $user = User::factory()->create(['role' => 'magazynier']);

        // Uwaga: nie sprawdzamy braku $item->inventory_no w całej stronie —
        // ten numer i tak zawsze pojawia się w <title>, niezależnie od
        // szablonu (to tylko tytuł karty/zadania druku, nie treść naklejki).
        // Sprawdzamy więc obecność/brak samego znacznika pola "no".
        $this->actingAs($user)->get(route('items.label', $item))
            ->assertOk()
            ->assertSee('32mm 20mm', false)
            ->assertSee('Wiertarka')
            ->assertDontSee('class="no"', false);
    }

    public function test_qr_only_template_shows_neither_name_nor_inventory_number(): void
    {
        $item = $this->makeItem('Wiertarka');
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->get(route('items.label', ['item' => $item, 'template' => '35x25']))
            ->assertOk()
            ->assertSee('35mm 25mm', false)
            ->assertDontSee('Wiertarka')
            ->assertDontSee('class="no"', false);
    }

    public function test_qr_name_and_inventory_number_template_shows_both(): void
    {
        $item = $this->makeItem('Wiertarka');
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->get(route('items.label', ['item' => $item, 'template' => '50x30']))
            ->assertOk()
            ->assertSee('50mm 30mm', false)
            ->assertSee('Wiertarka')
            ->assertSee('<span class="no">'.$item->inventory_no, false);
    }

    public function test_an_unknown_template_falls_back_to_the_default(): void
    {
        $item = $this->makeItem('Wiertarka');
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->get(route('items.label', ['item' => $item, 'template' => 'nie-ma-takiego']))
            ->assertOk()
            ->assertSee('32mm 20mm', false);
    }

    public function test_price_is_shown_only_when_requested_and_the_item_has_a_value(): void
    {
        $withValue = $this->makeItem('Wiertarka', value: 149.9);
        $withoutValue = $this->makeItem('Szlifierka');
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->get(route('items.label', $withValue))->assertDontSee('149,90 zł');

        $this->actingAs($user)->get(route('items.label', ['item' => $withValue, 'price' => 1]))
            ->assertSee('149,90 zł');

        // Cena zaznaczona, ale przedmiot bez wartości — nic nie ma się wywalić, po prostu brak linijki ceny.
        $this->actingAs($user)->get(route('items.label', ['item' => $withoutValue, 'price' => 1]))
            ->assertOk()
            ->assertDontSee('zł');
    }

    public function test_printing_labels_for_selected_items_shows_one_label_per_item(): void
    {
        $one = $this->makeItem('Wiertarka');
        $two = $this->makeItem('Szlifierka');
        $user = User::factory()->create(['role' => 'magazynier']);

        $response = $this->actingAs($user)->post(route('items.labels.print'), [
            'items' => [$one->id, $two->id],
        ]);

        $response->assertOk()
            ->assertSee('32mm 20mm', false)
            ->assertSee('Wiertarka')
            ->assertSee('Szlifierka');
        $this->assertSame(2, substr_count($response->getContent(), 'class="label'));
    }

    public function test_printing_labels_accepts_a_template_and_price_choice(): void
    {
        $item = $this->makeItem('Wiertarka', value: 99);
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->post(route('items.labels.print'), [
            'items' => [$item->id], 'template' => '50x30', 'price' => 1,
        ])
            ->assertOk()
            ->assertSee('50mm 30mm', false)
            ->assertSee($item->inventory_no)
            ->assertSee('99,00 zł');
    }

    public function test_printing_labels_rejects_an_unknown_template(): void
    {
        $item = $this->makeItem('Wiertarka');
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->post(route('items.labels.print'), [
            'items' => [$item->id], 'template' => 'nie-ma-takiego',
        ])->assertSessionHasErrors('template');
    }

    /** Toolbar na wydruku zbiorczym musi umieć resubmitować te same ID przy zmianie szablonu, patrz labels-print.blade.php. */
    public function test_bulk_print_page_carries_the_same_item_ids_forward_as_hidden_fields(): void
    {
        $one = $this->makeItem('Wiertarka');
        $two = $this->makeItem('Szlifierka');
        $user = User::factory()->create(['role' => 'magazynier']);

        $response = $this->actingAs($user)->post(route('items.labels.print'), [
            'items' => [$one->id, $two->id],
        ]);

        $response->assertSee('name="items[]" value="'.$one->id.'"', false)
            ->assertSee('name="items[]" value="'.$two->id.'"', false);
    }

    public function test_printing_labels_requires_at_least_one_item(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->post(route('items.labels.print'), ['items' => []])
            ->assertSessionHasErrors('items');
    }

    public function test_printing_labels_rejects_an_id_that_does_not_exist(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->post(route('items.labels.print'), ['items' => [999999]])
            ->assertSessionHasErrors('items.0');
    }

    /** Ten sam poziom uprawnien co pojedyncza etykieta/podglad przedmiotu — kazda zalogowana rola, patrz CLAUDE.md. */
    public function test_any_logged_in_role_can_print_labels(): void
    {
        $item = $this->makeItem('Wiertarka');
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->post(route('items.labels.print'), ['items' => [$item->id]])->assertOk();
    }

    /** Szablon/cena wybiera się dopiero na podglądzie (labels-print.blade.php), nie na /items — życzenie użytkownika. */
    public function test_items_index_offers_checkboxes_and_a_print_labels_button_without_template_picker(): void
    {
        $item = $this->makeItem('Wiertarka');
        $user = User::factory()->create(['role' => 'magazynier']);

        foreach (['list', 'grid'] as $view) {
            $this->actingAs($user)->get(route('items.index', ['view' => $view]))
                ->assertOk()
                ->assertSee(__('Print labels'))
                ->assertSee(__('Select all'))
                ->assertDontSee(__('Show price'))
                ->assertSee('name="items[]" value="'.$item->id.'"', false);
        }
    }

    private function makeItem(string $name, ?float $value = null): Item
    {
        static $n = 0;
        $n++;

        return Item::create([
            'inventory_no' => "NAR-BRAK-2026-0000{$n}", 'name' => $name, 'condition' => 'uzywany', 'status' => 'dostepny',
            'value' => $value,
        ]);
    }
}
