<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Loan;
use App\Support\BorrowerResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    /**
     * Wypożyczenie przedmiotu, zmienia też jego status na "wypożyczony".
     * "Komu": patrz BorrowerResolver (kontakt z listy, nowy w locie albo
     * zwykły tekst bez modułu Kontakty). Rezerwacja na ten termin dla kogoś
     * innego tylko ostrzega na karcie przedmiotu, nie blokuje (decyzja właściciela).
     */
    public function store(Request $request, Item $item)
    {
        // Przedmiot stacjonarny (maszyna w pracowni) nie wychodzi; rezerwuje się na nim czas.
        if ($item->stationary) {
            return back()->withErrors(['loan' => __('This item is stationary and can\'t be loaned out.')]);
        }
        // Przedmiotu w naprawie (moduł Serwis) nie da się wypożyczyć.
        if ($item->openRepair()->exists()) {
            return back()->withErrors(['loan' => __('This item is under repair. Complete the repair first.')]);
        }
        if ($item->currentLoan()->exists()) {
            return back()->withErrors(['loan' => __('This item is already on loan.')]);
        }

        $data = $request->validate([
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        [$contact, $borrowerName] = BorrowerResolver::resolve($request);

        Loan::lend($item, $contact, $borrowerName, $request->user(), $data);

        return back()->with('status', __('Item marked as on loan.'));
    }

    /** Zwrot jednego przedmiotu (także pojedynczej rzeczy z zestawu). */
    public function returnLoan(Request $request, Loan $loan)
    {
        abort_if($loan->returned_at, 404);

        $data = $request->validate([
            'condition_in' => ['nullable', Rule::in(array_keys(Item::CONDITIONS))],
            'return_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $loan->registerReturn($request->user(), $data['condition_in'] ?? null, $data['return_notes'] ?? null);

        return back()->with('status', __('Return registered.'));
    }
}
