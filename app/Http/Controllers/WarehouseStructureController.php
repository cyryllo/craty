<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use Illuminate\Support\Collection;

/**
 * Ustawienia → Struktura magazynu (moduł "Rozszerzony magazyn"): całe drzewo
 * magazyn → pomieszczenie → regał → półka → pojemnik na jednej stronie,
 * zamiast dwóch płaskich tabel (pomieszczenia, lokalizacje). W bazie nic się
 * nie zmienia — lokalizacje dalej są płaskimi wierszami z opcjonalnymi
 * rack/shelf/bin, drzewo powstaje wyłącznie przy wyświetlaniu.
 *
 * Liczby przy węzłach to przedmioty w magazynie (Item::inStock(), bez
 * sprzedanych/wycofanych) w danym miejscu łącznie ze wszystkim, co niżej —
 * regał liczy też swoje półki i pojemniki. Każda liczba prowadzi do /items
 * z filtrem tego miejsca (ItemController::index(), parametry location_*).
 */
class WarehouseStructureController extends Controller
{
    /** @var Collection<int, Collection<int, string>> nazwy przedmiotów per storage_location_id */
    private Collection $names;

    public function index()
    {
        $warehouses = Warehouse::with(['rooms' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get();

        // Nazwy przedmiotów (w magazynie) per lokalizacja — do dymka po najechaniu
        // na pojemnik/półkę/regał. Jedno zapytanie na całą stronę.
        $this->names = Item::inStock()->orderBy('name')->get(['name', 'storage_location_id'])
            ->groupBy('storage_location_id')
            ->map(fn ($items) => $items->pluck('name'));

        $locations = StorageLocation::query()
            ->withCount(['items' => fn ($q) => $q->inStock()])
            ->get()
            ->groupBy('warehouse_id');

        return view('warehouse-structure.index', [
            'names' => $this->names,
            'tree' => $warehouses->map(fn (Warehouse $warehouse) => $this->warehouseNode(
                $warehouse,
                $locations->get($warehouse->id, collect()),
            )),
        ]);
    }

    private function warehouseNode(Warehouse $warehouse, Collection $locations): array
    {
        $base = $locations->first(fn (StorageLocation $l) => $l->isBase());

        $rooms = $warehouse->rooms->map(function ($room) use ($locations) {
            $roomLocations = $locations->where('room_id', $room->id);
            $roomBase = $roomLocations->first(fn (StorageLocation $l) => $this->isRoomBase($l));

            return [
                'room' => $room,
                'base' => $roomBase,
                'count' => $roomLocations->sum('items_count'),
                'racks' => $this->racks($roomLocations->reject(fn (StorageLocation $l) => $this->isRoomBase($l))),
            ];
        });

        // Regały postawione w magazynie bez wskazania pomieszczenia.
        $loose = $locations->whereNull('room_id')->reject(fn (StorageLocation $l) => $l->isBase());

        return [
            'warehouse' => $warehouse,
            'base' => $base,
            'count' => $locations->sum('items_count'),
            'rooms' => $rooms,
            'looseRacks' => $this->racks($loose),
        ];
    }

    /** Pomieszczenie bez regału/półki/pojemnika — "całe pomieszczenie". */
    private function isRoomBase(StorageLocation $location): bool
    {
        return $location->room_id && ! $location->rack && ! $location->shelf && ! $location->bin;
    }

    /**
     * Grupuje lokalizacje w regały → półki → pojemniki. Każdy poziom może,
     * ale nie musi mieć własnej lokalizacji (np. istnieje R3-P2-K1, ale nie
     * sam "R3") — wtedy węzeł jest tylko grupą bez przycisku edycji.
     */
    private function racks(Collection $locations): Collection
    {
        return $locations
            ->groupBy(fn (StorageLocation $l) => (string) $l->rack)
            ->sortKeys(SORT_NATURAL)
            ->map(function (Collection $rackLocations, string $rack) {
                $shelves = $rackLocations
                    ->filter(fn (StorageLocation $l) => $l->shelf || $l->bin)
                    ->groupBy(fn (StorageLocation $l) => (string) $l->shelf)
                    ->sortKeys(SORT_NATURAL)
                    ->map(fn (Collection $shelfLocations, string $shelf) => [
                        'shelf' => $shelf,
                        'location' => $shelfLocations->first(fn (StorageLocation $l) => ! $l->bin),
                        'count' => $shelfLocations->sum('items_count'),
                        'names' => $this->namesIn($shelfLocations),
                        'bins' => $shelfLocations->filter(fn (StorageLocation $l) => $l->bin)
                            ->sortBy('bin', SORT_NATURAL)->values(),
                    ])
                    ->values();

                return [
                    'rack' => $rack,
                    'location' => $rackLocations->first(fn (StorageLocation $l) => ! $l->shelf && ! $l->bin),
                    'count' => $rackLocations->sum('items_count'),
                    'names' => $this->namesIn($rackLocations),
                    'shelves' => $shelves,
                ];
            })
            ->values();
    }

    /** Posortowane nazwy przedmiotów ze wszystkich podanych lokalizacji. */
    private function namesIn(Collection $locations): Collection
    {
        return $locations->flatMap(fn (StorageLocation $l) => $this->names->get($l->id, collect()))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();
    }
}
