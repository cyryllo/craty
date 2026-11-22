<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Loan;
use App\Models\Reservation;
use App\Support\BorrowerResolver;
use Illuminate\Http\Request;

/**
 * Rezerwacje przedmiotu na przyszły termin. Dwie rezerwacje tego samego
 * przedmiotu na zachodzące terminy są blokowane; wypożyczenie przedmiotu
 * zarezerwowanego dla kogoś innego tylko ostrzega (decyzja właściciela).
 * "Wydaj" zamienia rezerwację w zwykłe wypożyczenie z terminem zwrotu =
 * koniec rezerwacji.
 */
class ReservationController extends Controller
{
    public function store(Request $request, Item $item)
    {
        if (in_array($item->status, Item::OUT_OF_STOCK_STATUSES, true)) {
            return back()->withErrors(['reservation' => __('Sold or retired items can\'t be reserved.')]);
        }
        if ($item->stationary) {
            return back()->withErrors(['reservation' => __('This item is stationary and can\'t be loaned out.')]);
        }

        $data = $request->validate([
            'starts_on' => ['required', 'date', 'after_or_equal:today'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'reservation_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $clash = $item->reservations()->active()->overlapping($data['starts_on'], $data['ends_on'])->first();
        if ($clash) {
            return back()->withInput()->withErrors(['starts_on' => __('Already reserved for :name from :from to :to.', [
                'name' => $clash->borrowerLabel(), 'from' => $clash->starts_on->format('d.m.Y'), 'to' => $clash->ends_on->format('d.m.Y'),
            ])]);
        }

        [$contact, $borrowerName] = BorrowerResolver::resolve($request);

        $item->reservations()->create([
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'notes' => $data['reservation_notes'] ?? null,
            'contact_id' => $contact?->id,
            'borrower_name' => $borrowerName,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', __('Reservation saved.'));
    }

    public function destroy(Reservation $reservation)
    {
        abort_if($reservation->loan_id, 404);

        $reservation->delete();

        return back()->with('status', __('Reservation cancelled.'));
    }

    /** Wydanie zarezerwowanego przedmiotu: zwykłe wypożyczenie dla tej samej osoby. */
    public function issue(Request $request, Reservation $reservation)
    {
        abort_if($reservation->loan_id, 404);

        $item = $reservation->item;
        if ($reason = $item->unavailableReason()) {
            return back()->withErrors(['reservation' => __('This item can\'t be loaned right now: :reason.', ['reason' => $reason])]);
        }

        $loan = Loan::lend($item, $reservation->contact, $reservation->borrowerLabel(), $request->user(), [
            'due_at' => $reservation->ends_on->max(today()),
            'notes' => $reservation->notes,
        ]);
        $reservation->update(['loan_id' => $loan->id]);

        return redirect()->route('items.show', $item)->with('status', __('Item marked as on loan.'));
    }
}
