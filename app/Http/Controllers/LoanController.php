<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Item;
use App\Models\Loan;
use App\Support\Modules;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    /**
     * Wypożyczenie przedmiotu, zmienia też jego status na "wypożyczony".
     * Z modułem Kontakty: wybór kontaktu z listy albo nowy kontakt dodany w
     * locie (contact_id = "new"). Bez modułu: "komu" jako zwykły tekst.
     * W obu przypadkach borrower_name dostaje nazwę z tej chwili, żeby
     * protokół i historia przetrwały usunięcie kontaktu albo wyłączenie modułu.
     */
    public function store(Request $request, Item $item)
    {
        // Przedmiotu w naprawie (moduł Serwis) nie da się wypożyczyć.
        if ($item->openRepair()->exists()) {
            return back()->withErrors(['loan' => __('This item is under repair. Complete the repair first.')]);
        }

        $data = $request->validate([
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $contact = null;

        if (Modules::isEnabled('contacts')) {
            $request->validate(['contact_id' => ['required']], [
                'contact_id.required' => __('Choose who the item is loaned to.'),
            ]);

            if ($request->input('contact_id') === 'new') {
                $new = $request->validate(ContactController::rules('new_contact_'), [
                    'new_contact_name.required' => __('Enter the name of the new contact.'),
                ]);
                $contact = Contact::create([
                    'name' => $new['new_contact_name'],
                    'type' => $new['new_contact_type'] ?? 'osoba',
                    'phone' => $new['new_contact_phone'] ?? null,
                    'email' => $new['new_contact_email'] ?? null,
                ]);
            } else {
                $request->validate(['contact_id' => [Rule::exists('contacts', 'id')]]);
                $contact = Contact::findOrFail($request->input('contact_id'));
            }

            $borrowerName = $contact->name;
        } else {
            $borrowerName = $request->validate(['borrower_name' => ['required', 'string', 'max:255']])['borrower_name'];
        }

        $loan = $item->loans()->create([
            ...$data,
            'borrower_name' => $borrowerName,
            'contact_id' => $contact?->id,
            'issued_by' => $request->user()->id,
            'condition_out' => $item->condition,
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

    /**
     * Zwrot przedmiotu: przywraca status "dostępny", zapisuje stan przy
     * zwrocie i uwagi (do protokołu zwrotu) i aktualizuje stan przedmiotu
     * w kartotece, jeśli się zmienił (ItemObserver zapisze to w historii).
     */
    public function returnLoan(Request $request, Loan $loan)
    {
        abort_if($loan->returned_at, 404);

        $data = $request->validate([
            'condition_in' => ['nullable', Rule::in(array_keys(Item::CONDITIONS))],
            'return_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $loan->update([
            ...$data,
            'returned_at' => now(),
            'returned_to' => $request->user()->id,
        ]);

        $loan->item->update(array_filter([
            'status' => 'dostepny',
            'condition' => $data['condition_in'] ?? null,
        ]));

        $loan->item->histories()->create([
            'user_id' => $request->user()->id,
            'action' => 'returned',
            'old_value' => $loan->borrowerLabel(),
        ]);

        return back()->with('status', __('Return registered.'));
    }
}
