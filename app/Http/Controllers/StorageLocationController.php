<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StorageLocationController extends Controller
{
    public function index()
    {
        return view('storage-locations.index', [
            'locations' => StorageLocation::with(['warehouse', 'room'])->withCount('items')->get(),
        ]);
    }

    public function create()
    {
        return view('storage-locations.form', [
            'location' => new StorageLocation,
            'warehouses' => Warehouse::orderBy('name')->get(),
            'rooms' => Room::with('warehouse')->orderBy('warehouse_id')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        StorageLocation::create($this->validated($request));

        return redirect()->route('storage-locations.index')->with('status', __('Location added.'));
    }

    public function edit(StorageLocation $storageLocation)
    {
        return view('storage-locations.form', [
            'location' => $storageLocation,
            'warehouses' => Warehouse::orderBy('name')->get(),
            'rooms' => Room::with('warehouse')->orderBy('warehouse_id')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, StorageLocation $storageLocation)
    {
        $storageLocation->update($this->validated($request, $storageLocation));

        return redirect()->route('storage-locations.index')->with('status', __('Location updated.'));
    }

    public function destroy(StorageLocation $storageLocation)
    {
        if ($storageLocation->items()->exists()) {
            return back()->with('error', __('This location cannot be deleted because it has items in it.'));
        }

        $storageLocation->delete();

        return redirect()->route('storage-locations.index')->with('status', __('Location deleted.'));
    }

    private function validated(Request $request, ?StorageLocation $storageLocation = null): array
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'rack' => ['nullable', 'string', 'max:32'],
            'shelf' => ['nullable', 'string', 'max:32'],
            'bin' => ['nullable', 'string', 'max:32'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Pomieszczenie jest własnością konkretnego magazynu (rooms.warehouse_id)
        // — nie da się go wybrać z <select>a poza swoim magazynem (patrz
        // storage-locations/form.blade.php, optgroup per magazyn), ale ktoś
        // mógłby to obejść, więc sprawdzamy to samo jeszcze raz po stronie serwera.
        if (! empty($data['room_id']) && ! Room::where('id', $data['room_id'])->where('warehouse_id', $data['warehouse_id'])->exists()) {
            throw ValidationException::withMessages([
                'room_id' => [__('The selected room does not belong to the chosen warehouse.')],
            ]);
        }

        // storage_locations.code (regał/półka/pojemnik w obrębie magazynu) jest
        // unikalny w bazie, ale nie jest polem formularza — sam się buduje w
        // StorageLocation::buildCode(). Sprawdzamy duplikat z wyprzedzeniem,
        // żeby dostać czytelny błąd walidacji zamiast wyjątku unikalności z bazy.
        $candidate = new StorageLocation($data);
        $code = $candidate->buildCode();

        $duplicate = StorageLocation::where('code', $code)
            ->when($storageLocation, fn ($query) => $query->whereKeyNot($storageLocation))
            ->first();

        if ($duplicate) {
            // Nie tylko "już istnieje" — dopisujemy kod istniejącej lokalizacji i
            // przypominamy, że jedna lokalizacja i tak może trzymać wiele
            // przedmiotów naraz, żeby nie zachęcać do zakładania duplikatu
            // zamiast po prostu wybrania istniejącej na formularzu przedmiotu
            // (realne zgłoszenie użytkownika — trafił na ten błąd, bo nie
            // wiedział, że jeden pojemnik obsługuje kilka przedmiotów).
            throw ValidationException::withMessages([
                'combination' => [__('A location with this rack/shelf/bin combination already exists in this warehouse (code: :code). One location can already hold several items — pick the existing one on the item form instead of creating a new one.', ['code' => $duplicate->code])],
            ]);
        }

        return $data;
    }
}
