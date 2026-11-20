<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Loan;
use App\Models\LoanGroup;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Protokoły wydania i zwrotu jako PDF, generowane na żądanie z danych
 * wypożyczenia albo zestawu (nic nie jest zapisywane na dysku). Działają
 * niezależnie od modułu Kontakty: bez kontaktu protokół ma samą nazwę.
 */
class LoanProtocolController extends Controller
{
    public function issue(Loan $loan)
    {
        return $this->pdf(self::viewData($loan, 'issue'));
    }

    public function returned(Loan $loan)
    {
        abort_unless($loan->returned_at, 404);

        return $this->pdf(self::viewData($loan, 'return'));
    }

    public function groupIssue(LoanGroup $group)
    {
        return $this->pdf(self::groupViewData($group, 'issue'));
    }

    /** Protokół zwrotu zestawu obejmuje to, co już wróciło (zwrot może być częściowy). */
    public function groupReturned(LoanGroup $group)
    {
        abort_unless($group->loans()->whereNotNull('returned_at')->exists(), 404);

        return $this->pdf(self::groupViewData($group, 'return'));
    }

    /** Dane widoku wydzielone, żeby testy mogły sprawdzić treść protokołu bez parsowania PDF-a. */
    public static function viewData(Loan $loan, string $kind): array
    {
        $loan->loadMissing(['item', 'contact', 'borrower', 'issuer', 'receiver']);

        return self::common($kind, collect([$loan]), [
            'number' => $loan->protocolNumber(),
            'borrowerName' => $loan->borrowerLabel(),
            'contact' => $loan->contact,
            'issueDate' => $loan->borrowed_at,
            'dueDate' => $loan->due_at,
            'notes' => $loan->notes,
            'issuer' => $loan->issuer,
        ]);
    }

    public static function groupViewData(LoanGroup $group, string $kind): array
    {
        $group->loadMissing(['loans.item', 'loans.receiver', 'contact', 'issuer']);
        $loans = $kind === 'return' ? $group->loans->whereNotNull('returned_at')->values() : $group->loans;

        return self::common($kind, $loans, [
            'number' => $group->protocolNumber(),
            'borrowerName' => $group->borrowerLabel(),
            'contact' => $group->contact,
            'issueDate' => $group->borrowed_at,
            'dueDate' => $group->due_at,
            'notes' => $group->notes,
            'issuer' => $group->issuer,
        ]);
    }

    /** @param  Collection<int, Loan>  $loans */
    private static function common(string $kind, Collection $loans, array $data): array
    {
        $setting = AppSetting::current();

        // dompdf czyta obrazy z dysku (bez pobierania przez HTTP), a SVG logo
        // i tak nie przyjmujemy (patrz SecurityTest), więc wystarczy ścieżka.
        $logo = $setting->logo_path && Storage::disk('public')->exists($setting->logo_path)
            ? Storage::disk('public')->path($setting->logo_path)
            : null;

        $lastReturn = $loans->sortByDesc('returned_at')->first();

        return [
            ...$data,
            'kind' => $kind,
            'loans' => $loans,
            'returnDate' => $lastReturn?->returned_at,
            'receiver' => $lastReturn?->receiver,
            'appName' => $setting->effectiveName(),
            'logo' => $logo,
        ];
    }

    private function pdf(array $data)
    {
        $file = Str::slug(($data['kind'] === 'issue' ? __('Issue protocol') : __('Return protocol')).' '.$data['number']).'.pdf';

        return Pdf::loadView('loans.protocol', $data)->setPaper('a4')->stream($file);
    }
}
