<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Item;
use App\Models\Repair;
use App\Support\Modules;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Moduł Serwis: wysyłka przedmiotu do naprawy i jej zakończenie. Na czas
 * naprawy przedmiot ma status "w naprawie", po zakończeniu wraca do
 * statusu sprzed wysyłki.
 */
class RepairController extends Controller
{
    public function store(Request $request, Item $item)
    {
        if ($item->openRepair()->exists()) {
            return back()->withErrors(['repair' => __('This item is already under repair.')]);
        }
        if ($item->currentLoan()->exists()) {
            return back()->withErrors(['repair' => __('This item is on loan. Register the return first.')]);
        }

        $data = $request->validate([
            'fault_description' => ['required', 'string', 'max:5000'],
            'sent_at' => ['required', 'date'],
            'expected_at' => ['nullable', 'date', 'after_or_equal:sent_at'],
            'service_name' => ['nullable', 'string', 'max:255'],
            'contact_id' => ['nullable', Rule::exists('contacts', 'id')],
        ]);

        // Serwis z Kontaktów tylko przy włączonym module Kontakty; nazwa zapisana
        // tak jak przy wypożyczeniu, żeby przetrwała usunięcie kontaktu.
        $contact = Modules::isEnabled('contacts') && ! empty($data['contact_id']) ? Contact::find($data['contact_id']) : null;

        $repair = $item->repairs()->create([
            'fault_description' => $data['fault_description'],
            'sent_at' => $data['sent_at'],
            'expected_at' => $data['expected_at'] ?? null,
            'contact_id' => $contact?->id,
            'service_name' => $contact?->name ?? ($data['service_name'] ?? null),
            'previous_status' => $item->status,
            'created_by' => $request->user()->id,
        ]);

        $item->update(['status' => 'w_naprawie']);

        $item->histories()->create([
            'user_id' => $request->user()->id,
            'action' => 'repair_sent',
            'new_value' => $repair->serviceLabel(),
        ]);

        return back()->with('status', __('Item sent for repair.'));
    }

    public function complete(Request $request, Repair $repair)
    {
        abort_unless($repair->isOpen(), 404);

        $data = $request->validate([
            'returned_at' => ['required', 'date', 'after_or_equal:'.$repair->sent_at->toDateString()],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'work_done' => ['nullable', 'string', 'max:5000'],
            'condition' => ['nullable', Rule::in(array_keys(Item::CONDITIONS))],
        ]);

        $repair->update([
            'returned_at' => $data['returned_at'],
            'cost' => $data['cost'] ?? null,
            'work_done' => $data['work_done'] ?? null,
            'closed_by' => $request->user()->id,
        ]);

        // Powrót do statusu sprzed naprawy; "wypożyczony" i "w naprawie" nie mają
        // tu sensu (wypożyczonego nie da się wysłać), więc wtedy "dostępny".
        $status = in_array($repair->previous_status, [null, 'w_naprawie', 'wypozyczony'], true) ? 'dostepny' : $repair->previous_status;

        $item = $repair->item;
        $item->update(array_filter([
            'status' => $status,
            'condition' => $data['condition'] ?? null,
        ]));

        $item->histories()->create([
            'user_id' => $request->user()->id,
            'action' => 'repair_returned',
            'old_value' => $repair->serviceLabel(),
        ]);

        return back()->with('status', __('Repair completed.'));
    }
}
