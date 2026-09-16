<?php

namespace App\Http\Controllers;

use App\Services\ItemImporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Import masowy istniejącego spisu (TODO.md "Import masowy") — patrz
 * App\Services\ItemImporter po samą logikę parsowania/dopasowania.
 *
 * Wiersze z nierozpoznaną kategorią/lokalizacją nie tworzą przedmiotu od
 * razu — trafiają do sesji (`SESSION_KEY`) i czekają na potwierdzenie
 * przez `confirmUnassigned()` (przycisk "Dodaj do nieprzypisanych" w
 * widoku). Świadomie dwuetapowe, nie w pełni automatyczne — admin ma
 * zobaczyć, co dokładnie się nie rozpoznało, zanim cokolwiek naprawdę
 * powstanie w bazie.
 */
class ItemImportController extends Controller
{
    private const SESSION_KEY = 'import_pending_rows';

    public function create()
    {
        return view('items.import', [
            'result' => null,
            'pendingCount' => count(session(self::SESSION_KEY, [])),
        ]);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ItemImporter::HEADER, ';');
            fputcsv($handle, [
                'Wiertarka Bosch GSB 18V-55', 'NAR', 'M1-R3-P2', 'uzywany', 'dostepny',
                '450.00', 'SN-12345', '5901234123457', 'Komplet z akumulatorem i ładowarką',
            ], ';');
            fclose($handle);
        }, 'craty-import-szablon.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(Request $request, ItemImporter $importer)
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:csv,txt'],
        ]);

        $result = $importer->import($request->file('file')->getRealPath(), $request->user());

        session([self::SESSION_KEY => $result['pending_rows']]);

        return view('items.import', [
            'result' => $result,
            'pendingCount' => count($result['pending_rows']),
        ]);
    }

    /** Tworzy przedmioty odłożone przy ostatnim imporcie, bez kategorii/lokalizacji których kod się nie rozpoznał. */
    public function confirmUnassigned(Request $request, ItemImporter $importer)
    {
        $pendingRows = session(self::SESSION_KEY, []);

        abort_if($pendingRows === [], 404);

        $result = $importer->confirmUnassigned($pendingRows, $request->user());
        session()->forget(self::SESSION_KEY);

        return view('items.import', [
            'result' => ['created' => $result['created'], 'failed' => 0, 'pending' => 0, 'rows' => $result['rows']],
            'pendingCount' => 0,
        ]);
    }
}
