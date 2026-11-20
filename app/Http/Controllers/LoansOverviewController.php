<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Reservation;

/**
 * Strona "Wypożyczenia": co jest teraz poza magazynem (pojedynczo i w
 * zestawach) i nadchodzące rezerwacje, w jednym miejscu.
 */
class LoansOverviewController extends Controller
{
    public function index()
    {
        $loans = Loan::with(['item', 'contact', 'borrower', 'group'])
            ->whereNull('returned_at')
            ->orderByRaw('due_at is null')->orderBy('due_at')->orderBy('borrowed_at')
            ->get();

        return view('loans.index', [
            'singleLoans' => $loans->whereNull('loan_group_id')->values(),
            'groups' => $loans->whereNotNull('loan_group_id')->groupBy('loan_group_id')->map(fn ($groupLoans) => [
                'group' => $groupLoans->first()->group,
                'loans' => $groupLoans,
            ])->values(),
            'reservations' => Reservation::active()->with(['item', 'contact'])->orderBy('starts_on')->get(),
        ]);
    }
}
