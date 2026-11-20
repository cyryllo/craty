<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Consumable;
use App\Models\StorageLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Moduł Materiały eksploatacyjne (`module:consumables`). Ilość zmienia się
 * wyłącznie przez adjust() (pobierz / uzupełnij / ustaw stan), nigdy w
 * formularzu edycji, żeby każda zmiana miała wpis w historii ruchów.
 */
class ConsumableController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $consumables = Consumable::with(['storageLocation.warehouse', 'storageLocation.room', 'category'])
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($request->boolean('low'), fn ($q) => $q->low())
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('consumables.index', [
            'consumables' => $consumables,
            'search' => $search,
            'lowOnly' => $request->boolean('low'),
            'lowCount' => Consumable::low()->count(),
        ]);
    }

    public function create()
    {
        return view('consumables.form', ['consumable' => new Consumable, ...$this->formOptions()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([...$this->rules(), 'quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999']]);

        $consumable = Consumable::create($data);
        if ((float) ($data['quantity'] ?? 0) > 0) {
            $consumable->adjust((float) $data['quantity'], __('Initial stock'), $request->user());
        }

        return redirect()->route('consumables.show', $consumable)->with('status', __('Consumable added.'));
    }

    public function show(Consumable $consumable)
    {
        $consumable->load(['storageLocation.warehouse', 'storageLocation.room', 'category']);

        return view('consumables.show', [
            'consumable' => $consumable,
            'movements' => $consumable->movements()->with('user')->limit(50)->get(),
        ]);
    }

    public function edit(Consumable $consumable)
    {
        return view('consumables.form', ['consumable' => $consumable, ...$this->formOptions()]);
    }

    public function update(Request $request, Consumable $consumable)
    {
        $consumable->update($request->validate($this->rules()));

        return redirect()->route('consumables.show', $consumable)->with('status', __('Consumable saved.'));
    }

    public function destroy(Consumable $consumable)
    {
        $consumable->delete();

        return redirect()->route('consumables.index')->with('status', __('Consumable deleted.'));
    }

    /** Pobierz (take), uzupełnij (add) albo ustaw dokładny stan po przeliczeniu (set). */
    public function adjust(Request $request, Consumable $consumable)
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['take', 'add', 'set'])],
            'amount' => ['required', 'numeric', $request->input('mode') === 'set' ? 'min:0' : 'gt:0', 'max:999999999'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $amount = (float) $data['amount'];
        $change = match ($data['mode']) {
            'take' => -$amount,
            'add' => $amount,
            'set' => $amount - (float) $consumable->quantity,
        };
        $note = $data['note'] ?? ($data['mode'] === 'set' ? __('Stock count correction') : null);

        $movement = $consumable->adjust($change, $note, $request->user());

        return back()->with('status', __(':name: now :quantity :unit.', [
            'name' => $consumable->name,
            'quantity' => Consumable::formatQuantity($movement->quantity_after),
            'unit' => $consumable->unitLabel(),
        ]));
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', Rule::in(array_keys(Consumable::UNITS))],
            'min_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'storage_location_id' => ['nullable', 'exists:storage_locations,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function formOptions(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'locations' => StorageLocation::with(['warehouse', 'room'])->get()
                ->sortBy(fn (StorageLocation $location) => $location->label(), SORT_NATURAL)->values(),
        ];
    }
}
