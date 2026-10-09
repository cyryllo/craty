<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StorageLocationController extends Controller
{
    /**
     * Regał/półka/pojemnik trafiają do kodu lokalizacji, a ten do numeru
     * ewidencyjnego i nazwy pliku QR — tylko litery, cyfry, spacja, myślnik
     * i podkreślnik. Bez "/" i ".." (path traversal, pentest 2026-10-09).
     */
    private const SEGMENT_RULE = 'regex:/^[\pL\pN _-]+$/u';

    /** Dawna płaska lista — zastąpiona drzewem w Strukturze magazynu. */
    public function index()
    {
        return redirect()->route('warehouse-structure.index');
    }

    public function create(Request $request)
    {
        return view('storage-locations.form', [
            // Przyciski "+ Regał/Półka/Pojemnik" w Strukturze magazynu podają
            // miejsce w drzewie w adresie — formularz startuje już wypełniony.
            'location' => new StorageLocation($request->only(['warehouse_id', 'room_id', 'rack', 'shelf'])),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'rooms' => Room::with('warehouse')->orderBy('warehouse_id')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        StorageLocation::create($this->validated($request));

        return redirect()->route('warehouse-structure.index')->with('status', __('Location added.'));
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

        return redirect()->route('warehouse-structure.index')->with('status', __('Location updated.'));
    }

    /**
     * Przedmioty z usuwanej lokalizacji nie blokują usunięcia — trafiają
     * poziom wyżej: do "całego pomieszczenia", a bez pomieszczenia do
     * "całego magazynu". Samego "całego magazynu" usunąć się nie da — to
     * właściwie magazyn jako taki (usuwa się go razem z magazynem).
     */
    public function destroy(StorageLocation $storageLocation)
    {
        if ($storageLocation->isBase()) {
            return back()->with('error', __('The "whole warehouse" location cannot be deleted on its own — delete the warehouse instead.'));
        }

        $moved = 0;
        if ($storageLocation->items()->exists()) {
            $target = $storageLocation->room_id && ! $this->isRoomBase($storageLocation)
                ? StorageLocation::baseFor($storageLocation->warehouse_id, $storageLocation->room_id)
                : StorageLocation::baseFor($storageLocation->warehouse_id);
            $moved = $storageLocation->moveItemsTo($target);
        }

        $storageLocation->delete();

        return redirect()->route('warehouse-structure.index')->with('status', $moved
            ? __('Location deleted. :count items moved to: :target.', ['count' => $moved, 'target' => $target->label()])
            : __('Location deleted.'));
    }

    private function isRoomBase(StorageLocation $location): bool
    {
        return $location->room_id && ! $location->rack && ! $location->shelf && ! $location->bin;
    }

    private function validated(Request $request, ?StorageLocation $storageLocation = null): array
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'rack' => ['nullable', 'string', 'max:32', self::SEGMENT_RULE],
            'shelf' => ['nullable', 'string', 'max:32', self::SEGMENT_RULE],
            'bin' => ['nullable', 'string', 'max:32', self::SEGMENT_RULE],
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
