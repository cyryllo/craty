<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Item;
use App\Models\Loan;
use App\Models\LoanGroup;
use App\Support\BorrowerResolver;
use App\Support\Modules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Zestawy: kilka przedmiotów wydanych naraz jednej osobie, z jednym
 * protokołem. Przedmioty trafiają tu z serii skanów albo z zaznaczonych na
 * liście przedmiotów. Zwrot całości albo wybranych rzeczy na stronie
 * zestawu; pojedynczą rzecz da się też zwrócić z jej karty (zwykły zwrot).
 */
class LoanGroupController extends Controller
{
    public function create(Request $request)
    {
        $items = $this->requestedItems($request);

        return view('loan-groups.create', [
            'items' => $items,
            'contacts' => Modules::isEnabled('contacts') ? Contact::orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*' => ['integer', Rule::exists('items', 'id')],
        ], ['items.required' => __('Choose at least one item.')]);

        $data = $request->validate([
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $items = Item::whereIn('id', $request->input('items'))->get();
        $blocked = $items->filter(fn (Item $item) => $item->unavailableReason() !== null);
        if ($blocked->isNotEmpty()) {
            return back()->withInput()->withErrors(['items' => __('These items can\'t be loaned right now: :items', [
                'items' => $blocked->map(fn (Item $item) => $item->name.' ('.$item->unavailableReason().')')->implode(', '),
            ])]);
        }

        [$contact, $borrowerName] = BorrowerResolver::resolve($request);

        $group = DB::transaction(function () use ($items, $contact, $borrowerName, $data, $request) {
            $group = LoanGroup::create([
                ...$data,
                'contact_id' => $contact?->id,
                'borrower_name' => $borrowerName,
                'issued_by' => $request->user()->id,
                'borrowed_at' => now(),
            ]);

            foreach ($items as $item) {
                Loan::lend($item, $contact, $borrowerName, $request->user(), [...$data, 'loan_group_id' => $group->id]);
            }

            return $group;
        });

        return redirect()->route('loan-groups.show', $group)->with('status', __('Kit loaned out (:count items).', ['count' => $items->count()]));
    }

    public function show(LoanGroup $group)
    {
        $group->load(['loans.item', 'contact', 'issuer']);

        return view('loan-groups.show', compact('group'));
    }

    /** Zwrot zaznaczonych rzeczy z zestawu (domyślnie zaznaczone są wszystkie jeszcze niezwrócone). */
    public function returnItems(Request $request, LoanGroup $group)
    {
        $data = $request->validate([
            'returned' => ['required', 'array', 'min:1'],
            'returned.*' => ['integer'],
            'condition' => ['array'],
            'condition.*' => ['nullable', Rule::in(array_keys(Item::CONDITIONS))],
            'notes' => ['array'],
            'notes.*' => ['nullable', 'string', 'max:5000'],
        ], ['returned.required' => __('Tick at least one item to return.')]);

        $loans = $group->loans()->whereNull('returned_at')->whereIn('id', $data['returned'])->with('item')->get();

        foreach ($loans as $loan) {
            $loan->registerReturn($request->user(), $data['condition'][$loan->id] ?? null, $data['notes'][$loan->id] ?? null);
        }

        return back()->with('status', __('Return registered (:count items).', ['count' => $loans->count()]));
    }

    /** Przedmioty z adresu (?items[]=...), zachowując kolejność skanowania. */
    private function requestedItems(Request $request)
    {
        $ids = collect((array) $request->input('items', []))->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $items = Item::with('storageLocation.warehouse')->whereIn('id', $ids)->get()->keyBy('id');

        return $ids->map(fn ($id) => $items->get($id))->filter()->values();
    }
}
