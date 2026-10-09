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

        $loan = $item->loans()->create([
            ...$data,
            'borrowed_at' => now(),
        ]);

        $item->update(['status' => 'wypozyczony']);

        // Sama zmiana statusu w historii nie mówi, KOMU wypożyczono — osobny
        // wpis z nazwą wypożyczającego (i terminem zwrotu, jeśli podany).
        $item->histories()->create([
            'user_id' => $request->user()->id,
            'action' => 'loaned',
            'new_value' => $loan->borrowerLabel(),
            'old_value' => $loan->due_at?->format('d.m.Y'),
        ]);

        return back()->with('status', __('Item marked as on loan.'));
    }

    /** Zwrot przedmiotu — przywraca status "dostępny". */
    public function returnLoan(Loan $loan)
    {
        $loan->update(['returned_at' => now()]);
        $loan->item->update(['status' => 'dostepny']);

        $loan->item->histories()->create([
            'user_id' => auth()->id(),
            'action' => 'returned',
            'old_value' => $loan->borrowerLabel(),
        ]);

        return back()->with('status', __('Return registered.'));
    }
}
