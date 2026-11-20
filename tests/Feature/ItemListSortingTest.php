<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Lista przedmiotów: sortowanie kliknięciem w nagłówek i zapamiętywanie filtrów. */
class ItemListSortingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'magazynier']);

        $tools = Category::create(['name' => 'Narzędzia', 'code' => 'NAR']);
        $cables = Category::create(['name' => 'Akcesoria', 'code' => 'AKC']);
        foreach ([['Bormaszyna', 300, $tools], ['Agregat', 1500, null], ['Czajnik', 80, $cables]] as $i => [$name, $value, $category]) {
            Item::create([
                'inventory_no' => 'X-'.$i, 'name' => $name, 'value' => $value, 'category_id' => $category?->id,
                'condition' => 'uzywany', 'status' => 'dostepny',
            ])->forceFill(['created_at' => now()->subDays(3 - $i)])->saveQuietly();
        }
    }

    public function test_default_order_is_newest_first(): void
    {
        $this->actingAs($this->user)->get(route('items.index'))->assertSeeInOrder(['Czajnik', 'Agregat', 'Bormaszyna']);
    }

    public function test_sorting_by_name_value_and_category(): void
    {
        $get = fn (array $params) => $this->actingAs($this->user)->get(route('items.index', [...$params, 'view' => 'list']));

        $get(['sort' => 'name', 'dir' => 'asc'])->assertSeeInOrder(['Agregat', 'Bormaszyna', 'Czajnik']);
        $get(['sort' => 'name', 'dir' => 'desc'])->assertSeeInOrder(['Czajnik', 'Bormaszyna', 'Agregat']);
        $get(['sort' => 'value', 'dir' => 'desc'])->assertSeeInOrder(['Agregat', 'Bormaszyna', 'Czajnik']);
        // Po nazwie kategorii: Akcesoria (Czajnik) przed Narzędziami (Bormaszyna).
        $get(['sort' => 'category', 'dir' => 'asc'])->assertSeeInOrder(['Czajnik', 'Bormaszyna']);
    }

    public function test_unknown_sort_falls_back_to_newest_first(): void
    {
        $this->actingAs($this->user)->get(route('items.index', ['sort' => 'password', 'dir' => 'sideways']))
            ->assertOk()->assertSeeInOrder(['Czajnik', 'Agregat', 'Bormaszyna']);
    }

    public function test_header_link_toggles_the_direction(): void
    {
        $this->actingAs($this->user)->get(route('items.index', ['view' => 'list', 'sort' => 'name', 'dir' => 'asc']))
            ->assertSee('sort=name&amp;dir=desc', false)
            ->assertSee('sort=value&amp;dir=asc', false);
    }

    public function test_filters_and_sorting_are_remembered_until_cleared(): void
    {
        $this->actingAs($this->user)->get(route('items.index', ['q' => 'Bor', 'sort' => 'name', 'dir' => 'desc', 'f' => 1]))
            ->assertOk()->assertSee('Bormaszyna')->assertDontSee('Czajnik');

        // Wejście na samą listę (np. z menu) przywraca filtry, z widocznym paskiem.
        $redirect = $this->actingAs($this->user)->get(route('items.index'));
        $redirect->assertRedirect();
        $this->assertStringContainsString('q=Bor', $redirect->headers->get('Location'));
        $this->assertStringContainsString('sort=name', $redirect->headers->get('Location'));
        $this->actingAs($this->user)->get($redirect->headers->get('Location'))
            ->assertSee(__('Clear filters'))->assertDontSee('Czajnik');

        // Wyczyść: wszystko wraca do domyślnego stanu i nic się już nie przywraca.
        $this->actingAs($this->user)->get(route('items.index', ['clear' => 1]))->assertRedirect(route('items.index'));
        $this->actingAs($this->user)->get(route('items.index'))
            ->assertOk()->assertSee('Czajnik')->assertDontSee(__('Clear filters'));
    }

    public function test_submitting_an_empty_filter_form_forgets_previous_filters(): void
    {
        $this->actingAs($this->user)->get(route('items.index', ['q' => 'Bor', 'f' => 1]));
        $this->actingAs($this->user)->get(route('items.index', ['q' => '', 'category_id' => '', 'status' => '', 'f' => 1]))->assertSee('Czajnik');

        $this->actingAs($this->user)->get(route('items.index'))->assertOk()->assertSee('Czajnik');
    }
}
