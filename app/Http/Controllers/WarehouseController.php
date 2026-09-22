<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        return view('warehouses.index', [
            'warehouses' => Warehouse::withCount('storageLocations')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('warehouses.form', ['warehouse' => new Warehouse]);
    }

    public function store(Request $request)
    {
        $warehouse = Warehouse::create($this->validated($request));

        // Nie każdy chce od razu rozpisywać regały/półki/pojemniki — bez tego
        // magazynu w ogóle nie dało się wybrać na formularzu przedmiotu
        // (patrz migracja add_base_location_for_warehouses_without_one).
        // Ta "bazowa" lokalizacja (bez rack/shelf/bin) daje wybór "cały
        // magazyn", a StorageLocation::label() ją odpowiednio opisuje.
        StorageLocation::create(['warehouse_id' => $warehouse->id]);

        return redirect()->route('warehouses.index')->with('status', __('Warehouse added.'));
    }

    public function edit(Warehouse $warehouse)
    {
        return view('warehouses.form', ['warehouse' => $warehouse]);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $warehouse->update($this->validated($request, $warehouse));

        return redirect()->route('warehouses.index')->with('status', __('Warehouse updated.'));
    }

    public function destroy(Warehouse $warehouse)
    {
        // Nie samo "czy magazyn ma jakąś lokalizację" — od store() każdy
        // magazyn zawsze ma co najmniej bazową (patrz wyżej), więc ten
        // warunek nigdy by nie przepuścił żadnego usunięcia. Liczy się,
        // czy w którejkolwiek z jego lokalizacji faktycznie są przedmioty.
        $hasItems = Item::whereIn('storage_location_id', $warehouse->storageLocations()->pluck('id'))->exists();

        if ($hasItems) {
            return back()->with('error', __('This warehouse cannot be deleted because it has items in it.'));
        }

        $warehouse->delete(); // lokalizacje kaskadowo (storage_locations.warehouse_id->cascadeOnDelete())

        return redirect()->route('warehouses.index')->with('status', __('Warehouse deleted.'));
    }

    private function validated(Request $request, ?Warehouse $warehouse = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:8', 'unique:warehouses,code,'.($warehouse?->id)],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $data['code'] = mb_strtoupper($data['code']);

        return $data;
    }
}
