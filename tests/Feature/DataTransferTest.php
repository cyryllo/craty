<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Consumable;
use App\Models\Contact;
use App\Models\Item;
use App\Models\Loan;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Strona "Import i eksport": kontakty i materiały (dopasowanie po nazwie,
 * bez podglądu), raporty CSV i dostęp przy wyłączonych modułach.
 */
class DataTransferTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'magazynier']);
    }

    public function test_contacts_import_matches_by_name_and_round_trips(): void
    {
        $existing = Contact::create(['name' => 'Jan Kowalski', 'type' => 'osoba', 'phone' => '111']);

        $this->upload('data-transfer.contacts.import', 'contacts_file', "nazwa;typ;telefon;email\njan kowalski;;222;\nSerwis Elektro;Repair service;;biuro@elektro.test\nZły;kosmita;;\nZły mail;;;nie-mail\n")
            ->assertRedirect(route('data-transfer.index'));

        $report = session('import_report');
        $this->assertSame([1, 1, 0, 2], [$report['created'], $report['updated'], $report['unchanged'], count($report['errors'])]);
        $this->assertSame('222', $existing->fresh()->phone);
        $this->assertSame('osoba', $existing->fresh()->type);
        $this->assertSame('serwis', Contact::where('name', 'Serwis Elektro')->value('type'));

        $csv = $this->actingAs($this->user)->get(route('data-transfer.contacts.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('"Serwis Elektro";serwis;;biuro@elektro.test', $csv);

        $this->upload('data-transfer.contacts.import', 'contacts_file', $csv);
        $this->assertSame(2, session('import_report')['unchanged']);
        $this->assertSame(2, Contact::count());
    }

    public function test_consumables_import_creates_and_corrects_stock_through_movements(): void
    {
        AppSetting::current()->fill(['module_consumables_enabled' => true])->save();
        $category = Category::create(['name' => 'Elektryka', 'code' => 'ELE']);
        $tape = Consumable::create(['name' => 'Taśma izolacyjna', 'unit' => 'szt', 'min_quantity' => 5]);
        $tape->adjust(10, null, $this->user);

        $this->upload('data-transfer.consumables.import', 'consumables_file', "nazwa;jednostka;ilosc;stan_minimalny;kategoria_kod\nTaśma izolacyjna;;7;;\nPrzewód 2x1,5;m;25,5;10;ele\nKlej;szt;-1;;\n")
            ->assertRedirect(route('data-transfer.index'));

        $report = session('import_report');
        $this->assertSame([1, 1, 1], [$report['created'], $report['updated'], count($report['errors'])]);
        $this->assertSame(7.0, (float) $tape->fresh()->quantity);
        $this->assertSame(-3.0, (float) $tape->movements()->latest('id')->first()->change);
        $wire = Consumable::where('name', 'Przewód 2x1,5')->firstOrFail();
        $this->assertSame(['m', 25.5, 10.0, $category->id], [$wire->unit, (float) $wire->quantity, (float) $wire->min_quantity, $wire->category_id]);
        $this->assertSame(1, $wire->movements()->count());

        $csv = $this->actingAs($this->user)->get(route('data-transfer.consumables.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('"Przewód 2x1,5";m;25,500;10,000;ELE', $csv);
    }

    public function test_disabled_modules_hide_their_sections_and_routes(): void
    {
        AppSetting::current()->fill(['module_contacts_enabled' => false, 'module_loans_enabled' => false])->save();

        $this->actingAs($this->user)->get(route('data-transfer.index'))->assertOk()
            ->assertDontSee(route('data-transfer.contacts.export'))
            ->assertDontSee(route('data-transfer.consumables.export'))
            ->assertDontSee(route('data-transfer.loans.export'));
        $this->actingAs($this->user)->get(route('data-transfer.contacts.export'))->assertNotFound();
        $this->actingAs($this->user)->get(route('data-transfer.consumables.export'))->assertNotFound();
        $this->actingAs($this->user)->get(route('data-transfer.loans.export'))->assertNotFound();
    }

    public function test_loan_history_export_lists_returned_and_open_loans(): void
    {
        $item = Item::create(['name' => 'Wiertarka', 'inventory_no' => 'NAR-1', 'condition' => 'uzywany', 'status' => 'dostepny']);
        Loan::create(['item_id' => $item->id, 'borrower_name' => 'Anna', 'borrowed_at' => '2026-01-10', 'returned_at' => '2026-01-12', 'issued_by' => $this->user->id]);
        Loan::create(['item_id' => $item->id, 'borrower_name' => 'Bartek', 'borrowed_at' => '2026-03-01', 'due_at' => '2026-03-05']);

        $csv = $this->actingAs($this->user)->get(route('data-transfer.loans.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Wiertarka;NAR-1;Anna;2026-01-10;;2026-01-12', $csv);
        $this->assertStringContainsString('Bartek;2026-03-01;2026-03-05;;'.__('yes'), $csv);

        $from = $this->actingAs($this->user)->get(route('data-transfer.loans.export', ['from' => '2026-02-01']))->streamedContent();
        $this->assertStringNotContainsString('Anna', $from);
    }

    public function test_stocktake_report_csv_only_for_finished_stocktakes(): void
    {
        $warehouse = Warehouse::create(['name' => 'Magazyn', 'code' => 'M1']);
        $location = StorageLocation::create(['warehouse_id' => $warehouse->id, 'rack' => '1']);
        $present = Item::create(['name' => 'Jest', 'inventory_no' => 'A-1', 'condition' => 'uzywany', 'status' => 'dostepny', 'storage_location_id' => $location->id]);
        Item::create(['name' => 'Brakuje', 'inventory_no' => 'A-2', 'condition' => 'uzywany', 'status' => 'dostepny', 'storage_location_id' => $location->id]);

        $this->actingAs($this->user)->post(route('stocktakes.store'), ['location_id' => $location->id]);
        $stocktake = \App\Models\Stocktake::firstOrFail();
        $this->actingAs($this->user)->get(route('stocktakes.report-csv', $stocktake))->assertNotFound();

        $this->actingAs($this->user)->postJson(route('stocktakes.scan', $stocktake), ['item_id' => $present->id]);
        $this->actingAs($this->user)->post(route('stocktakes.finish', $stocktake));

        $csv = $this->actingAs($this->user)->get(route('stocktakes.report-csv', $stocktake))->assertOk()->streamedContent();
        $this->assertStringContainsString(__('Missing').';Brakuje;A-2', $csv);
        $this->assertStringContainsString('"'.__('In place').'";Jest;A-1', $csv);
        $this->actingAs($this->user)->get(route('data-transfer.index'))->assertSee(route('stocktakes.report-csv', $stocktake));
    }

    public function test_activity_log_export_is_admin_only_and_uses_the_page_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ActivityLog::record('auth.login', $admin, [], $admin);
        ActivityLog::record('data.created', $admin, [], $admin);

        $this->actingAs($this->user)->get(route('settings.activity.export'))->assertForbidden();

        $csv = $this->actingAs($admin)->get(route('settings.activity.export', ['category' => 'security']))->assertOk()->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBFkiedy;kto;zdarzenie", $csv);
        $this->assertStringContainsString(ActivityLog::where('event', 'auth.login')->first()->eventLabel(), $csv);
        $this->assertStringNotContainsString(ActivityLog::where('event', 'data.created')->first()->eventLabel(), $csv);
        $this->actingAs($admin)->get(route('data-transfer.index'))->assertSee(route('settings.activity.export'));
    }

    private function upload(string $route, string $field, string $content)
    {
        return $this->actingAs($this->user)->post(route($route), [$field => UploadedFile::fake()->createWithContent('plik.csv', $content)]);
    }
}
