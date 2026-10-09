<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Room;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    /** Dawna płaska lista — zastąpiona drzewem w Strukturze magazynu. */
    public function index()
    {
        return redirect()->route('warehouse-structure.index');
    }

    public function create(Request $request)
    {
        return view('rooms.form', [
            // "+ Pomieszczenie" przy magazynie w Strukturze magazynu podaje ?warehouse_id=.
            'room' => new Room(['warehouse_id' => $request->integer('warehouse_id') ?: null]),
            'warehouses' => Warehouse::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $room = Room::create($this->validated($request));

        // Tak samo jak magazyn dostaje bazową lokalizację przy tworzeniu
        // (WarehouseController::store()) — pomieszczenie ma być wybieralne
        // od razu, bez konieczności dopisywania regału/półki/pojemnika,
        // jeśli komuś to wystarcza jako poziom szczegółowości.
        StorageLocation::create(['warehouse_id' => $room->warehouse_id, 'room_id' => $room->id]);

        return redirect()->route('warehouse-structure.index')->with('status', __('Room added.'));
    }

    public function edit(Room $room)
    {
        return view('rooms.form', ['room' => $room]);
    }

    public function update(Request $request, Room $room)
    {
        $room->update($this->validated($request, $room));

        return redirect()->route('warehouse-structure.index')->with('status', __('Room updated.'));
    }

    /**
     * Przedmioty z usuwanego pomieszczenia (z każdego jego regału/półki/
     * pojemnika) nie blokują usunięcia — przechodzą do "całego magazynu".
     */
    public function destroy(Room $room)
    {
        $moved = 0;
        $locationIds = $room->storageLocations()->pluck('id');
        if (Item::whereIn('storage_location_id', $locationIds)->exists()) {
            $target = StorageLocation::baseFor($room->warehouse_id);
            $moved = $room->storageLocations()->get()->sum(fn (StorageLocation $l) => $l->moveItemsTo($target));
        }

        $room->delete(); // lokalizacje kaskadowo (storage_locations.room_id ->cascadeOnDelete())

        if ($moved) {
            return redirect()->route('warehouse-structure.index')
                ->with('status', __('Room deleted. :count items moved to: :target.', ['count' => $moved, 'target' => $target->label()]));
        }

        return redirect()->route('warehouse-structure.index')->with('status', __('Room deleted.'));
    }

    /**
     * Magazyn pomieszczenia jest ustawiany tylko przy tworzeniu i potem
     * niezmienny — storage_locations.warehouse_id jest zdenormalizowane
     * (żeby dało się filtrować/walidować lokalizacje bez joina przez
     * pomieszczenie), więc pozwolenie na "przeniesienie" pomieszczenia do
     * innego magazynu po fakcie rozjechałoby te dwa pola na jego już
     * istniejących lokalizacjach.
     */
    private function validated(Request $request, ?Room $room = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:16', 'alpha_dash:ascii',
                Rule::unique('rooms', 'code')
                    ->where('warehouse_id', $room?->warehouse_id ?? $request->input('warehouse_id'))
                    ->ignore($room),
            ],
        ];

        if (! $room) {
            $rules['warehouse_id'] = ['required', 'exists:warehouses,id'];
        }

        $data = $request->validate($rules);
        $data['code'] = mb_strtoupper($data['code']);

        return $data;
    }
}
