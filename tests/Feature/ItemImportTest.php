<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemHistory;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryNumberGenerator;
use App\Services\ItemImporter;
use App\Services\QrCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Import i eksport przedmiotów (strona "Import i eksport"): podgląd bez
 * zapisu, zatwierdzenie, masowa zmiana po nr_ewidencyjny, eksport z filtrami
 * i powrót eksportu importem bez zmian.
 */
class ItemImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create(['role' => 'magazynier']);
    }

    public function test_hub_page_is_linked_from_settings_and_the_items_list_has_no_import_button(): void
    {
        $this->actingAs($this->user)->get(route('settings.index'))->assertOk()->assertSee(route('data-transfer.index'));
        $this->actingAs($this->user)->get(route('data-transfer.index'))->assertOk()
            ->assertSee(route('items.export', ['wzor' => 1]))
            ->assertDontSee(route('settings.activity.export')); // log tylko dla admina
        $this->actingAs($this->user)->get(route('items.index', ['f' => 1]))->assertOk()->assertDontSee('import-eksport');
    }

    public function test_template_has_the_export_header_only(): void
    {
        $content = $this->actingAs($this->user)->get(route('items.export', ['wzor' => 1]))->assertOk()->streamedContent();

        $this->assertSame("\xEF\xBB\xBF".implode(';', ItemImporter::EXPORT_COLUMNS)."\n", $content);
    }

    public function test_preview_saves_nothing_and_confirm_creates_items_matched_by_code(): void
    {
        $category = Category::create(['name' => 'Narzędzia', 'code' => 'NAR']);
        $warehouse = Warehouse::create(['name' => 'Magazyn główny', 'code' => 'M1']);
        $location = StorageLocation::create(['warehouse_id' => $warehouse->id, 'rack' => '3', 'shelf' => '2']);

        $this->preview(['nazwa', 'kategoria_kod', 'lokalizacja_kod', 'stan', 'wartosc', 'nr_seryjny', 'ean', 'data_zakupu', 'gwarancja_do', 'stacjonarny', 'opis'], [
            ['Wiertarka Bosch', 'nar', $location->code, 'Nowy', '1 450,50', 'SN-1', '5901234123457', '15.03.2025', '2027-03-15', 'tak', 'Opis testowy'],
        ])->assertOk()->assertSee(__('New items: :count', ['count' => 1]))->assertSee(__('Confirm import'));
        $this->assertSame(0, Item::count());

        $this->actingAs($this->user)->post(route('data-transfer.items.apply'))->assertOk()->assertSee(__('Added: :count', ['count' => 1]));

        $item = Item::firstOrFail();
        $this->assertSame('Wiertarka Bosch', $item->name);
        $this->assertSame($category->id, $item->category_id);
        $this->assertSame($location->id, $item->storage_location_id);
        $this->assertSame('nowy', $item->condition);
        $this->assertSame('dostepny', $item->status);
        $this->assertSame(1450.5, (float) $item->value);
        $this->assertSame('2025-03-15', $item->purchased_at->format('Y-m-d'));
        $this->assertSame('2027-03-15', $item->warranty_until->format('Y-m-d'));
        $this->assertTrue((bool) $item->stationary);
        $this->assertStringStartsWith('NAR-', $item->inventory_no);
        $this->assertNotNull($item->qr_path);
        $this->assertSame($this->user->id, $item->created_by);

        // Plan zużyty: drugie zatwierdzenie nic nie dodaje.
        $this->actingAs($this->user)->post(route('data-transfer.items.apply'))->assertRedirect(route('data-transfer.index'));
        $this->assertSame(1, Item::count());
    }

    public function test_unknown_code_on_a_new_item_is_a_warning_and_the_item_is_added_without_it(): void
    {
        $this->preview(['nazwa', 'kategoria_kod', 'lokalizacja_kod'], [['Wiertarka', 'NIEISTNIEJE', 'BRAK-LOK']])
            ->assertSee(__('Category ":code" was not recognized.', ['code' => 'NIEISTNIEJE']).' '.__('The item will be added without a category.'))
            ->assertSee(__('Location ":code" was not recognized.', ['code' => 'BRAK-LOK']))
            ->assertSee(__('New items: :count', ['count' => 1]));

        $this->actingAs($this->user)->post(route('data-transfer.items.apply'))->assertOk();
        $item = Item::firstOrFail();
        $this->assertNull($item->category_id);
        $this->assertStringStartsWith('GEN-BRAK-', $item->inventory_no);
    }

    public function test_rows_with_errors_are_listed_and_skipped_while_the_rest_imports(): void
    {
        $this->preview(['nazwa', 'stan', 'wartosc', 'data_zakupu', 'stacjonarny', 'przeglad_co_miesiecy'], [
            ['', '', '', '', '', ''],
            ['Zła wartość', '', 'dużo', '', '', ''],
            ['Zły stan', 'jak-nowy', '', '', '', ''],
            ['Zła data', '', '', '2025-13-45', '', ''],
            ['Zły znacznik', '', '', '', 'może', ''],
            ['Zły przegląd', '', '', '', '', '0'],
            ['Dobry', '', '', '', '', '12'],
        ])->assertSee(__('Errors: :count', ['count' => 5]))
            ->assertSee(__('Value is not a number: :value', ['value' => 'dużo']))
            ->assertSee(__('Unknown condition value: :value', ['value' => 'jak-nowy']))
            ->assertSee(__('Not a date (use 2026-11-25 or 25.11.2026): :value', ['value' => '2025-13-45']))
            ->assertSee(__('Stationary must be "tak" or "nie": :value', ['value' => 'może']))
            ->assertSee(__('New items: :count', ['count' => 1]));

        $this->actingAs($this->user)->post(route('data-transfer.items.apply'))->assertOk();
        $this->assertSame(['Dobry'], Item::pluck('name')->all());
        $this->assertSame(12, (int) Item::first()->inspection_interval_months);
    }

    public function test_header_needs_nazwa_or_nr_ewidencyjny(): void
    {
        $this->preview(['kolumna_a', 'kolumna_b'], [['x', 'y']])
            ->assertSee(__('The file needs a "nazwa" column (new items) or an "nr_ewidencyjny" column (changes to existing items) in its header row.'))
            ->assertDontSee(__('Confirm import'));
    }

    public function test_rows_with_inventory_number_update_only_filled_cells_and_log_history(): void
    {
        $category = Category::create(['name' => 'Narzędzia', 'code' => 'NAR']);
        $item = $this->item(['name' => 'Wiertarka', 'value' => 100, 'serial_number' => 'SN-OLD', 'description' => 'Zostaje']);
        $untouched = $this->item(['name' => 'Młotek', 'value' => 20]);

        $this->preview(['nr_ewidencyjny', 'nazwa', 'kategoria_kod', 'wartosc', 'nr_seryjny', 'status', 'opis'], [
            [$item->inventory_no, 'Wiertarka udarowa', 'NAR', '120', '', 'w naprawie', ''],
            [$untouched->inventory_no, 'Młotek', '', '20,00', '', '', ''],
        ])->assertSee(__('Changed: :count', ['count' => 1]))
            ->assertSee(__('Unchanged: :count', ['count' => 1]))
            ->assertSee('Wiertarka udarowa');
        $this->assertSame('Wiertarka', $item->fresh()->name);

        $this->actingAs($this->user)->post(route('data-transfer.items.apply'))->assertOk()->assertSee(__('Changed: :count', ['count' => 1]));

        $item->refresh();
        $this->assertSame('Wiertarka udarowa', $item->name);
        $this->assertSame($category->id, $item->category_id);
        $this->assertSame(120.0, (float) $item->value);
        $this->assertSame('w_naprawie', $item->status);
        $this->assertSame('SN-OLD', $item->serial_number);
        $this->assertSame('Zostaje', $item->description);
        $this->assertTrue(ItemHistory::where('item_id', $item->id)->where('field', 'name')->exists());
    }

    public function test_update_with_unknown_inventory_number_or_code_is_an_error(): void
    {
        $item = $this->item(['name' => 'Wiertarka']);

        $this->preview(['nr_ewidencyjny', 'kategoria_kod'], [
            ['XXX-0001', ''],
            [$item->inventory_no, 'LITEROWKA'],
        ])->assertSee(__('There is no item with inventory number :number.', ['number' => 'XXX-0001']))
            ->assertSee(__('Category ":code" was not recognized.', ['code' => 'LITEROWKA']))
            ->assertSee(__('Errors: :count', ['count' => 2]))
            ->assertDontSee(__('Confirm import'));
    }

    public function test_export_respects_filters_and_round_trips_without_changes(): void
    {
        $category = Category::create(['name' => 'Narzędzia', 'code' => 'NAR']);
        $other = Category::create(['name' => 'Inne', 'code' => 'INN']);
        $item = $this->item(['name' => '=Wiertarka "Bosch"; 18V', 'category_id' => $category->id, 'value' => 1234.5, 'purchased_at' => '2025-01-02', 'description' => "Dwie\nlinie"]);
        $this->item(['name' => 'Sprzedany', 'category_id' => $category->id, 'status' => 'sprzedany']);
        $this->item(['name' => 'Inna kategoria', 'category_id' => $other->id]);

        $csv = $this->actingAs($this->user)->get(route('items.export', ['category_id' => $category->id]))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=Wiertarka", $csv); // ochrona przed formułą
        $this->assertStringContainsString('1234,50', $csv);
        $this->assertStringNotContainsString('Sprzedany', $csv);
        $this->assertStringNotContainsString('Inna kategoria', $csv);

        $all = $this->actingAs($this->user)->get(route('items.export', ['status' => 'all']))->streamedContent();
        $this->assertStringContainsString('Sprzedany', $all);

        $this->actingAs($this->user)->post(route('data-transfer.items.preview'), [
            'file' => UploadedFile::fake()->createWithContent('eksport.csv', $csv),
        ])->assertOk()->assertSee(__('Unchanged: :count', ['count' => 1]))->assertSee(__('Changed: :count', ['count' => 0]));
        $this->assertSame([], session('items_import_plan'));
        $this->assertSame('=Wiertarka "Bosch"; 18V', $item->fresh()->name);
    }

    public function test_windows_1250_and_comma_separated_files_are_read(): void
    {
        $csv = iconv('UTF-8', 'WINDOWS-1250//IGNORE', "nazwa,opis\nSzlifierka kątowa,\"Ostrożnie, pęknięta\"\n");

        $this->actingAs($this->user)->post(route('data-transfer.items.preview'), [
            'file' => UploadedFile::fake()->createWithContent('spis.csv', $csv),
        ])->assertOk();
        $this->actingAs($this->user)->post(route('data-transfer.items.apply'));

        $item = Item::firstOrFail();
        $this->assertSame('Szlifierka kątowa', $item->name);
        $this->assertSame('Ostrożnie, pęknięta', $item->description);
    }

    public function test_import_stops_after_the_configured_row_limit(): void
    {
        $this->app->instance(ItemImporter::class, new ItemImporter(
            app(InventoryNumberGenerator::class), app(QrCodeGenerator::class), maxRows: 2,
        ));

        $this->preview(['nazwa'], [['P1'], ['P2'], ['P3']])
            ->assertSee(__('Import stopped after :max rows, split the file into smaller batches.', ['max' => 2]));
        $this->actingAs($this->user)->post(route('data-transfer.items.apply'));
        $this->assertSame(2, Item::count());
    }

    /** @param  array<int, string>  $header @param  array<int, array<int, string>>  $rows */
    private function preview(array $header, array $rows)
    {
        $lines = [implode(';', $header)];
        foreach ($rows as $row) {
            $lines[] = implode(';', $row);
        }

        return $this->actingAs($this->user)->post(route('data-transfer.items.preview'), [
            'file' => UploadedFile::fake()->createWithContent('spis.csv', implode("\n", $lines)."\n"),
        ]);
    }

    private function item(array $attributes): Item
    {
        static $n = 0;
        $n++;

        return Item::create($attributes + ['inventory_no' => 'TST-'.$n, 'condition' => 'uzywany', 'status' => 'dostepny']);
    }
}
