<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Consumable;
use App\Models\Contact;
use App\Models\Item;
use App\Models\Loan;
use App\Models\Stocktake;
use App\Services\ItemImporter;
use App\Services\SimpleCsvImporter;
use App\Support\Csv;
use Illuminate\Http\Request;

/**
 * Strona "Import i eksport" (link z Ustawień, podstawowa sekcja, bo
 * magazynier też importuje przedmioty). Świadomie nie pod "ustawienia/*":
 * tamte trasy są tylko dla admina (RouteAccessTest). Import przedmiotów ma
 * podgląd przed zapisem, kontakty i materiały wchodzą od razu (dopasowanie
 * po nazwie), a na końcu są raporty tylko do pobrania.
 */
class DataTransferController extends Controller
{
    private const PLAN_KEY = 'items_import_plan';

    public function index()
    {
        return view('data-transfer.index', [
            'categories' => Category::orderBy('name')->get(),
            'stocktakes' => Stocktake::whereNotNull('finished_at')->latest('finished_at')->limit(50)->get(),
        ]);
    }

    /** Podgląd importu przedmiotów: nic nie zapisuje, plan czeka w sesji na "Zatwierdź". */
    public function previewItems(Request $request, ItemImporter $importer)
    {
        $request->validate(['file' => ['required', 'file', 'extensions:csv,txt', 'max:10240']]);

        $analysis = $importer->analyze($request->file('file')->getRealPath());
        session([self::PLAN_KEY => $analysis['plan']]);

        return view('data-transfer.items-import', ['analysis' => $analysis, 'result' => null]);
    }

    public function applyItems(Request $request, ItemImporter $importer)
    {
        $plan = session()->pull(self::PLAN_KEY);
        if (! $plan) {
            return redirect()->route('data-transfer.index')->with('status', __('Nothing to import. Upload the file again.'));
        }

        return view('data-transfer.items-import', ['analysis' => null, 'result' => $importer->apply($plan, $request->user())]);
    }

    public function exportContacts()
    {
        $rows = (function () {
            foreach (Contact::orderBy('name')->lazy(500) as $contact) {
                yield SimpleCsvImporter::contactRow($contact);
            }
        })();

        return Csv::download('craty-kontakty-'.now()->format('Y-m-d').'.csv', SimpleCsvImporter::CONTACT_COLUMNS, $rows);
    }

    public function importContacts(Request $request, SimpleCsvImporter $importer)
    {
        $request->validate(['contacts_file' => ['required', 'file', 'extensions:csv,txt', 'max:10240']]);
        $report = $importer->importContacts($request->file('contacts_file')->getRealPath());

        return redirect()->route('data-transfer.index')->with('import_report', ['title' => __('Contacts'), ...$report]);
    }

    public function exportConsumables()
    {
        $rows = (function () {
            foreach (Consumable::with('category', 'storageLocation')->orderBy('name')->lazy(500) as $consumable) {
                yield SimpleCsvImporter::consumableRow($consumable);
            }
        })();

        return Csv::download('craty-materialy-'.now()->format('Y-m-d').'.csv', SimpleCsvImporter::CONSUMABLE_COLUMNS, $rows);
    }

    public function importConsumables(Request $request, SimpleCsvImporter $importer)
    {
        $request->validate(['consumables_file' => ['required', 'file', 'extensions:csv,txt', 'max:10240']]);
        $report = $importer->importConsumables($request->file('consumables_file')->getRealPath(), $request->user());

        return redirect()->route('data-transfer.index')->with('import_report', ['title' => __('Consumables'), ...$report]);
    }

    /** Historia wypożyczeń (wszystkie, także zwrócone), opcjonalnie od daty. */
    public function exportLoans(Request $request)
    {
        $from = Csv::date($request->query('from'));
        $rows = (function () use ($from) {
            $query = Loan::with('item', 'contact', 'borrower', 'issuer', 'receiver', 'group')
                ->when($from, fn ($q) => $q->whereDate('borrowed_at', '>=', $from))
                ->orderBy('borrowed_at')->orderBy('id');
            foreach ($query->lazy(500) as $loan) {
                yield [
                    $loan->protocolNumber(), $loan->group?->protocolNumber(), $loan->item?->name, $loan->item?->inventory_no,
                    $loan->borrowerLabel(), $loan->borrowed_at?->format('Y-m-d'), $loan->due_at?->format('Y-m-d'),
                    $loan->returned_at?->format('Y-m-d'), $loan->isOverdue() ? __('yes') : '',
                    Loan::conditionLabelFor($loan->condition_out), Loan::conditionLabelFor($loan->condition_in),
                    $loan->issuer?->name, $loan->receiver?->name, $loan->notes, $loan->return_notes,
                ];
            }
        })();

        return Csv::download('craty-wypozyczenia-'.now()->format('Y-m-d').'.csv', [
            'nr_protokolu', 'zestaw', 'przedmiot', 'nr_ewidencyjny', 'komu', 'wydano', 'termin_zwrotu', 'zwrocono',
            'po_terminie', 'stan_przy_wydaniu', 'stan_przy_zwrocie', 'wydal', 'przyjal', 'uwagi', 'uwagi_przy_zwrocie',
        ], $rows);
    }
}
