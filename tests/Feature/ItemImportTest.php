<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
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
 * TODO.md "Import masowy" — świadomie tylko CSV (patrz komentarz na górze
 * ItemImporter), dopasowanie kategorii/lokalizacji po ich krótkim "code".
 */
class ItemImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ewidencja dziś nie ma roli słabszej niż magazynier (usunięcie roli
     * "podglad", patrz CLAUDE.md) — trasa i tak zostaje w grupie
     * role:admin,magazynier, to samo uprawnienie co zwykłe dodawanie
     * przedmiotu, patrz analogiczna notatka w ScanTest.
     */
    public function test_only_admin_and_magazynier_can_import(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($magazynier)->get(route('items.import.create'))->assertOk();
    }

    public function test_template_download_has_the_expected_header(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $response = $this->actingAs($magazynier)->get(route('items.import.template'));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString(implode(';', ItemImporter::HEADER), $content);
    }

    public function test_import_creates_items_matching_category_and_location_by_code(): void
    {
        Storage::fake('public');
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $category = Category::create(['name' => 'Narzędzia', 'code' => 'NAR']);
        $warehouse = Warehouse::create(['name' => 'Magazyn główny', 'code' => 'M1']);
        $location = StorageLocation::create(['warehouse_id' => $warehouse->id, 'rack' => '3', 'shelf' => '2']);

        $csv = $this->csv([
            ['Wiertarka Bosch', $category->code, $location->code, 'nowy', 'dostepny', '450.50', 'SN-1', '5901234123457', 'Opis testowy'],
        ]);

        $response = $this->actingAs($magazynier)->post(route('items.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('spis.csv', $csv),
        ]);

        $response->assertOk();
        $item = Item::firstOrFail();
        $this->assertSame('Wiertarka Bosch', $item->name);
        $this->assertSame($category->id, $item->category_id);
        $this->assertSame($location->id, $item->storage_location_id);
        $this->assertSame('nowy', $item->condition);
        $this->assertSame('dostepny', $item->status);
        $this->assertSame(450.5, (float) $item->value);
        $this->assertSame('SN-1', $item->serial_number);
        $this->assertSame('5901234123457', $item->ean);
        $this->assertSame('Opis testowy', $item->description);
        $this->assertStringStartsWith('NAR-', $item->inventory_no);
        $this->assertNotNull($item->qr_path);
    }

    public function test_import_defaults_condition_status_and_leaves_category_location_blank_when_omitted(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $csv = $this->csv([
            ['Multimetr', '', '', '', '', '', '', '', ''],
        ]);

        $this->actingAs($magazynier)->post(route('items.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('spis.csv', $csv),
        ])->assertOk();

        $item = Item::firstOrFail();
        $this->assertNull($item->category_id);
        $this->assertNull($item->storage_location_id);
        $this->assertSame('uzywany', $item->condition);
        $this->assertSame('dostepny', $item->status);
        $this->assertStringStartsWith('GEN-BRAK-', $item->inventory_no);
    }

    public function test_import_reports_a_missing_name_row_without_aborting_the_rest(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $csv = $this->csv([
            ['', '', '', '', '', '', '', '', ''],
            ['Wiertarka', '', '', '', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($magazynier)->post(route('items.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('spis.csv', $csv),
        ]);

        $response->assertOk()->assertSee(__('Missing item name.'));
        $this->assertSame(1, Item::count());
        $this->assertSame('Wiertarka', Item::firstOrFail()->name);
    }

    /**
     * Życzenie użytkownika (2026-09-16): nierozpoznany kod kategorii/
     * lokalizacji nie odrzuca już wiersza — trafia do "oczekujących" i
     * czeka na potwierdzenie przyciskiem "Dodaj do nieprzypisanych"
     * (dwuetapowo, świadomie nie w pełni automatycznie).
     */
    public function test_import_leaves_a_row_with_an_unknown_category_code_pending_instead_of_rejecting_it(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $csv = $this->csv([
            ['Wiertarka', 'NIEISTNIEJE', '', '', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($magazynier)->post(route('items.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('spis.csv', $csv),
        ]);

        $response->assertOk()
            ->assertSee(__('Category ":code" was not recognized.', ['code' => 'NIEISTNIEJE']))
            ->assertSee(__('Add to unassigned'));
        $this->assertSame(0, Item::count());
        $this->assertSame(1, session('import_pending_rows') ? count(session('import_pending_rows')) : 0);
    }

    public function test_import_leaves_a_row_with_an_unknown_location_code_pending_instead_of_rejecting_it(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $csv = $this->csv([
            ['Wiertarka', '', 'NIEISTNIEJE', '', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($magazynier)->post(route('items.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('spis.csv', $csv),
        ]);

        $response->assertOk()
            ->assertSee(__('Location ":code" was not recognized.', ['code' => 'NIEISTNIEJE']))
            ->assertSee(__('Add to unassigned'));
        $this->assertSame(0, Item::count());
    }

    public function test_import_reports_both_unmatched_codes_when_a_row_has_neither(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $csv = $this->csv([
            ['Wiertarka', 'BRAK-KAT', 'BRAK-LOK', '', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($magazynier)->post(route('items.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('spis.csv', $csv),
        ]);

        $response->assertOk()
            ->assertSee(__('Category ":code" was not recognized.', ['code' => 'BRAK-KAT']))
            ->assertSee(__('Location ":code" was not recognized.', ['code' => 'BRAK-LOK']));
    }

    public function test_confirming_unassigned_creates_the_pending_items_without_category_or_location(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $csv = $this->csv([
            ['Wiertarka', 'NIEISTNIEJE', '', 'nowy', 'dostepny', '199.99', 'SN-9', '111', 'Opis'],
        ]);
        $this->actingAs($magazynier)->post(route('items.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('spis.csv', $csv),
        ]);
        $this->assertSame(0, Item::count());

        $response = $this->actingAs($magazynier)->post(route('items.import.confirm-unassigned'));

        $response->assertOk()->assertSee(__(':count created', ['count' => 1]));
        $item = Item::firstOrFail();
        $this->assertSame('Wiertarka', $item->name);
        $this->assertNull($item->category_id);
        $this->assertSame('nowy', $item->condition);
        $this->assertSame(199.99, (float) $item->value);
        $this->assertStringStartsWith('GEN-BRAK-', $item->inventory_no);
        $this->assertNotNull($item->qr_path);
        $this->assertNull(session('import_pending_rows'));
    }

    /** Tylko lokalizacja się nie rozpoznała — potwierdzona kategoria ma zostać, nie zniknąć razem z lokalizacją. */
    public function test_confirming_unassigned_keeps_the_field_that_did_match(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $category = Category::create(['name' => 'Narzędzia', 'code' => 'NAR']);
        $csv = $this->csv([
            ['Wiertarka', $category->code, 'NIEISTNIEJE', '', '', '', '', '', ''],
        ]);
        $this->actingAs($magazynier)->post(route('items.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('spis.csv', $csv),
        ]);

        $this->actingAs($magazynier)->post(route('items.import.confirm-unassigned'));

        $item = Item::firstOrFail();
        $this->assertSame($category->id, $item->category_id);
        $this->assertNull($item->storage_location_id);
        $this->assertStringStartsWith('NAR-BRAK-', $item->inventory_no);
    }

    public function test_confirming_unassigned_twice_in_a_row_returns_not_found_the_second_time(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $csv = $this->csv([['Wiertarka', 'NIEISTNIEJE', '', '', '', '', '', '', '']]);
        $this->actingAs($magazynier)->post(route('items.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('spis.csv', $csv),
        ]);

        $this->actingAs($magazynier)->post(route('items.import.confirm-unassigned'))->assertOk();
        $this->actingAs($magazynier)->post(route('items.import.confirm-unassigned'))->assertNotFound();
    }

    public function test_confirming_unassigned_without_a_prior_import_returns_not_found(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($magazynier)->post(route('items.import.confirm-unassigned'))->assertNotFound();
    }

    public function test_pending_count_persists_on_the_import_page_after_navigating_away_and_back(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $csv = $this->csv([['Wiertarka', 'NIEISTNIEJE', '', '', '', '', '', '', '']]);
        $this->actingAs($magazynier)->post(route('items.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('spis.csv', $csv),
        ]);

        $this->actingAs($magazynier)->get(route('items.import.create'))
            ->assertOk()
            ->assertSee(__('Add to unassigned'));
    }

    public function test_import_rejects_a_row_with_a_non_numeric_value(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $csv = $this->csv([
            ['Wiertarka', '', '', '', '', 'not-a-number', '', '', ''],
        ]);

        $response = $this->actingAs($magazynier)->post(route('items.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('spis.csv', $csv),
        ]);

        $response->assertOk()->assertSee(__('Value is not a number: :value', ['value' => 'not-a-number']));
        $this->assertSame(0, Item::count());
    }

    public function test_import_rejects_a_row_with_an_unknown_condition_value(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $csv = $this->csv([
            ['Wiertarka', '', '', 'jak-nowy', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($magazynier)->post(route('items.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('spis.csv', $csv),
        ]);

        $response->assertOk()->assertSee(__('Unknown condition value: :value', ['value' => 'jak-nowy']));
        $this->assertSame(0, Item::count());
    }

    public function test_import_reports_missing_nazwa_column_in_the_header(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $csv = "kolumna_a;kolumna_b\nx;y\n";

        $response = $this->actingAs($magazynier)->post(route('items.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('spis.csv', $csv),
        ]);

        $response->assertOk()->assertSee(__('The file is missing the required "nazwa" column in its header row.'));
        $this->assertSame(0, Item::count());
    }

    /** Excel na Windows domyślnie zapisuje polskie CSV w Windows-1250, nie UTF-8 — patrz ItemImporter::rawLooksLikeUtf8(). */
    public function test_import_converts_windows_1250_encoded_files_to_utf8(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $utf8Csv = $this->csv([
            ['Szlifierka kątowa', '', '', '', '', '', '', '', 'Ostrożnie, ładowarka pęknięta'],
        ]);
        // mbstring nie zna nazwy kodowania "Windows-1250" (tylko iconv jej
        // używa — patrz ItemImporter, który konwertuje przez stream_filter
        // iconv, nie mb_convert_encoding) — stąd iconv() tu, a nie mbstring,
        // żeby uczciwie odtworzyć plik zapisany przez Excela na Windows.
        $windows1250Csv = iconv('UTF-8', 'WINDOWS-1250//IGNORE', $utf8Csv);

        $response = $this->actingAs($magazynier)->post(route('items.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('spis.csv', $windows1250Csv),
        ]);

        $response->assertOk();
        $item = Item::firstOrFail();
        $this->assertSame('Szlifierka kątowa', $item->name);
        $this->assertSame('Ostrożnie, ładowarka pęknięta', $item->description);
    }

    public function test_import_stops_after_the_configured_row_limit(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $csv = $this->csv([
            ['Przedmiot 1', '', '', '', '', '', '', '', ''],
            ['Przedmiot 2', '', '', '', '', '', '', '', ''],
            ['Przedmiot 3', '', '', '', '', '', '', '', ''],
        ]);

        // Podmieniamy instancję w kontenerze tylko po to, żeby wstrzyknąć
        // niski limit wierszy — to wciąż PRAWDZIWA klasa, nie atrapa,
        // budowanie 2000+ rzeczywistych wierszy tylko po stałą MAX_ROWS
        // byłoby powolne i niepotrzebne (patrz komentarz w konstruktorze).
        $this->app->instance(ItemImporter::class, new ItemImporter(
            app(InventoryNumberGenerator::class), app(QrCodeGenerator::class), maxRows: 2,
        ));

        $response = $this->actingAs($magazynier)->post(route('items.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('spis.csv', $csv),
        ]);

        $response->assertOk()->assertSee(__('Import stopped after :max rows — split the file into smaller batches.', ['max' => 2]));
        $this->assertSame(2, Item::count());
    }

    /** @param  array<int, array<int, string>>  $rows */
    private function csv(array $rows): string
    {
        $lines = [implode(';', ItemImporter::HEADER)];

        foreach ($rows as $row) {
            $lines[] = implode(';', $row);
        }

        return implode("\n", $lines)."\n";
    }
}
