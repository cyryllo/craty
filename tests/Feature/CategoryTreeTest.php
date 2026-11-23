<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kategorie jako drzewo: karty kategorii głównych, podkategorie, sumy, brak pętli. */
class CategoryTreeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'magazynier']);
    }

    private function item(Category $category, string $status = 'dostepny'): Item
    {
        static $no = 0;

        return Item::create(['inventory_no' => 'C-'.++$no, 'name' => 'Rzecz '.$no, 'category_id' => $category->id, 'condition' => 'uzywany', 'status' => $status]);
    }

    public function test_tree_shows_nested_categories_with_totals(): void
    {
        $tools = Category::create(['name' => 'Narzędzia', 'code' => 'NAR']);
        $power = Category::create(['name' => 'Elektronarzędzia', 'code' => 'ELN', 'parent_id' => $tools->id]);
        $drills = Category::create(['name' => 'Wiertarki', 'code' => 'WIE', 'parent_id' => $power->id]);
        $this->item($tools);
        $this->item($power);
        $this->item($drills);
        $this->item($drills);
        $this->item($drills, 'sprzedany'); // sprzedane się nie liczą

        $html = $this->actingAs($this->user)->get(route('categories.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/Narzędzia.*Elektronarzędzia.*Wiertarki/s', $html);
        $this->assertStringContainsString('(4)', $html); // Narzędzia razem
        $this->assertStringContainsString('(3)', $html); // Elektronarzędzia razem
        $this->assertStringContainsString(route('categories.create', ['parent_id' => $power->id]), $html);
        $this->assertStringContainsString(e(route('items.index', ['category_id' => $drills->id, 'f' => 1])), $html);
    }

    public function test_add_subcategory_prefills_the_parent(): void
    {
        $tools = Category::create(['name' => 'Narzędzia', 'code' => 'NAR']);

        $this->actingAs($this->user)->get(route('categories.create', ['parent_id' => $tools->id]))
            ->assertOk()->assertSee('value="'.$tools->id.'" selected', false);
    }

    public function test_a_category_cannot_be_moved_under_its_own_subcategory(): void
    {
        $tools = Category::create(['name' => 'Narzędzia', 'code' => 'NAR']);
        $power = Category::create(['name' => 'Elektronarzędzia', 'code' => 'ELN', 'parent_id' => $tools->id]);
        $drills = Category::create(['name' => 'Wiertarki', 'code' => 'WIE', 'parent_id' => $power->id]);

        foreach ([$drills->id, $power->id, $tools->id] as $parent) {
            $this->actingAs($this->user)->put(route('categories.update', $tools), ['name' => 'Narzędzia', 'code' => 'NAR', 'parent_id' => $parent])
                ->assertSessionHasErrors('parent_id');
        }
        $this->assertNull($tools->fresh()->parent_id);

        // Edycja nie podsuwa tej kategorii ani jej podkategorii jako nadrzędnej.
        $this->actingAs($this->user)->get(route('categories.edit', $tools))
            ->assertDontSee('value="'.$drills->id.'"', false)->assertDontSee('value="'.$tools->id.'"', false);

        // Przeniesienie w bok jest w porządku.
        $other = Category::create(['name' => 'Elektronika', 'code' => 'ELE']);
        $this->actingAs($this->user)->put(route('categories.update', $drills), ['name' => 'Wiertarki', 'code' => 'WIE', 'parent_id' => $other->id])
            ->assertSessionHasNoErrors();
    }

    public function test_delete_lives_on_the_edit_form_and_is_refused_when_used(): void
    {
        $tools = Category::create(['name' => 'Narzędzia', 'code' => 'NAR']);
        $empty = Category::create(['name' => 'Pusta', 'code' => 'PUS']);
        $this->item($tools);

        // Adres usuwania to ten sam URL co zapis formularza, więc sprawdzamy przycisk.
        $this->actingAs($this->user)->get(route('categories.edit', $tools))->assertOk()->assertDontSee(__('Delete category'));
        $this->actingAs($this->user)->get(route('categories.edit', $empty))->assertOk()->assertSee(__('Delete category'));

        $this->actingAs($this->user)->delete(route('categories.destroy', $tools));
        $this->assertModelExists($tools);
        $this->actingAs($this->user)->delete(route('categories.destroy', $empty))->assertRedirect(route('categories.index'));
        $this->assertModelMissing($empty);
    }

    public function test_names_are_escaped_in_the_tree_and_the_parent_list(): void
    {
        $evil = '<script>alert(1)</script>';
        $root = Category::create(['name' => $evil, 'code' => 'XSS']);
        Category::create(['name' => $evil, 'code' => 'XS2', 'parent_id' => $root->id]);

        foreach ([route('categories.index'), route('categories.create')] as $url) {
            $this->actingAs($this->user)->get($url)->assertOk()->assertDontSee($evil, false);
        }
    }
}
