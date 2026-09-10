<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Loan;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    /** Wypożyczenie przedmiotu — zmienia też jego status na "wypożyczony". */
    public function store(Request $request, Item $item)
    {
        $data = $request->validate([
            'borrower_name' => ['required', 'string', 'max:255'],
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string'],
        ]);

        $item->loans()->create([
            ...$data,
            'borrowed_at' => now(),
        ]);

        $item->update(['status' => 'wypozyczony']);

        return back()->with('status', __('Item marked as on loan.'));
    }

    /** Zwrot przedmiotu — przywraca status "dostępny". */
    public function returnLoan(Loan $loan)
    {
        $loan->update(['returned_at' => now()]);
        $loan->item->update(['status' => 'dostepny']);

        return back()->with('status', __('Return registered.'));
    }
}
