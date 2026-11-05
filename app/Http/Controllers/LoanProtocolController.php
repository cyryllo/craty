<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Loan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Protokoły wydania i zwrotu jako PDF, generowane na żądanie z danych
 * wypożyczenia (nic nie jest zapisywane na dysku). Działają niezależnie od
 * modułu Kontakty: bez kontaktu protokół ma samą nazwę z borrower_name.
 */
class LoanProtocolController extends Controller
{
    public function issue(Loan $loan)
    {
        return $this->pdf($loan, 'issue');
    }

    public function returned(Loan $loan)
    {
        abort_unless($loan->returned_at, 404);

        return $this->pdf($loan, 'return');
    }

    /** Dane widoku wydzielone, żeby testy mogły sprawdzić treść protokołu bez parsowania PDF-a. */
    public static function viewData(Loan $loan, string $kind): array
    {
        $loan->loadMissing(['item', 'contact', 'borrower', 'issuer', 'receiver']);
        $setting = AppSetting::current();

        // dompdf czyta obrazy z dysku (bez pobierania przez HTTP), a SVG logo
        // i tak nie przyjmujemy (patrz SecurityTest), więc wystarczy ścieżka.
        $logo = $setting->logo_path && Storage::disk('public')->exists($setting->logo_path)
            ? Storage::disk('public')->path($setting->logo_path)
            : null;

        return [
            'loan' => $loan,
            'item' => $loan->item,
            'contact' => $loan->contact,
            'kind' => $kind,
            'appName' => $setting->effectiveName(),
            'logo' => $logo,
        ];
    }

    private function pdf(Loan $loan, string $kind)
    {
        $file = Str::slug(($kind === 'issue' ? __('Issue protocol') : __('Return protocol')).' '.$loan->protocolNumber()).'.pdf';

        return Pdf::loadView('loans.protocol', self::viewData($loan, $kind))
            ->setPaper('a4')
            ->stream($file);
    }
}
