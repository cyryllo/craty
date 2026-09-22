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
    public function index()
    {
        return view('rooms.index', [
            'rooms' => Room::with('warehouse')->withCount('storageLocations')->orderBy('warehouse_id')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('rooms.form', [
            'room' => new Room,
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

        return redirect()->route('rooms.index')->with('status', __('Room added.'));
    }

    public function edit(Room $room)
    {
        return view('rooms.form', ['room' => $room]);
    }

    public function update(Request $request, Room $room)
    {
        $room->update($this->validated($request, $room));

        return redirect()->route('rooms.index')->with('status', __('Room updated.'));
    }

    public function destroy(Room $room)
    {
        $hasItems = Item::whereIn('storage_location_id', $room->storageLocations()->pluck('id'))->exists();

        if ($hasItems) {
            return back()->with('error', __('This room cannot be deleted because it has items in it.'));
        }

        $room->delete(); // lokalizacje kaskadowo (storage_locations.room_id ->cascadeOnDelete())

        return redirect()->route('rooms.index')->with('status', __('Room deleted.'));
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
                'required', 'string', 'max:16',
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
