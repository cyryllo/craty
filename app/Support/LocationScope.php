<?php

namespace App\Support;

use App\Models\Room;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Miejsce" w magazynie jako zestaw parametrów: dokładna lokalizacja
 * (storage_location_id) albo gałąź drzewa (warehouse_id + opcjonalnie
 * room_id, gdzie "none" = bez pomieszczenia, + rack + shelf), czyli to
 * miejsce i wszystko pod nim. Ten sam zapis w adresie listy przedmiotów
 * (linki ze Struktury magazynu) i w zakresie inwentaryzacji, więc oba
 * liczą przedmioty dokładnie tak samo.
 */
class LocationScope
{
    public const PARAMS = ['storage_location_id', 'warehouse_id', 'room_id', 'rack', 'shelf'];

    /** @param  array<string, mixed>  $params */
    public function __construct(public readonly array $params)
    {
    }

    /** @param  array<string, mixed>  $input */
    public static function fromArray(array $input): self
    {
        return new self(array_filter(
            array_intersect_key($input, array_flip(self::PARAMS)),
            fn ($value) => $value !== null && $value !== '',
        ));
    }

    /** Gałąź zaczynająca się w tej lokalizacji (kosz na samym dole = dokładnie ta lokalizacja). */
    public static function forLocation(StorageLocation $location): self
    {
        if ($location->bin) {
            return new self(['storage_location_id' => $location->id]);
        }

        return self::fromArray([
            'warehouse_id' => $location->warehouse_id,
            // Lokalizacja bazowa magazynu (bez pomieszczenia i regału) = cały magazyn.
            'room_id' => $location->room_id ?? ($location->rack ? 'none' : null),
            'rack' => $location->rack,
            'shelf' => $location->shelf,
        ]);
    }

    public function isEmpty(): bool
    {
        return ! isset($this->params['storage_location_id']) && ! isset($this->params['warehouse_id']);
    }

    /** Zawęża zapytanie o przedmioty do tego miejsca. */
    public function apply(Builder $query): void
    {
        $p = $this->params;

        if (isset($p['storage_location_id'])) {
            $query->where('storage_location_id', (int) $p['storage_location_id']);

            return;
        }

        if (! isset($p['warehouse_id'])) {
            return;
        }

        $query->whereHas('storageLocation', fn ($q) => $this->constrainLocation($q));
    }

    /** Czy lokalizacja przedmiotu mieści się w tym miejscu. */
    public function contains(?StorageLocation $location): bool
    {
        if (! $location) {
            return false;
        }

        return StorageLocation::whereKey($location->id)->where(fn ($q) => $this->constrainLocation($q))->exists();
    }

    /** Zapytanie o lokalizacje należące do tego miejsca. */
    private function constrainLocation($q): void
    {
        $p = $this->params;

        if (isset($p['storage_location_id'])) {
            $q->whereKey((int) $p['storage_location_id']);

            return;
        }

        $q->where('warehouse_id', (int) ($p['warehouse_id'] ?? 0));

        if (($p['room_id'] ?? null) === 'none') {
            $q->whereNull('room_id');
        } elseif (isset($p['room_id'])) {
            $q->where('room_id', (int) $p['room_id']);
        }

        foreach (['rack', 'shelf'] as $field) {
            if (isset($p[$field])) {
                $q->where($field, (string) $p[$field]);
            }
        }
    }

    /**
     * Lokalizacja odpowiadająca samemu miejscu (np. regał R1, bez półki),
     * tworzona, jeśli jeszcze nie istnieje: tam trafia przedmiot przy
     * "przenieś tutaj" w raporcie inwentaryzacji.
     */
    public function ownLocation(): ?StorageLocation
    {
        $p = $this->params;

        if (isset($p['storage_location_id'])) {
            return StorageLocation::find((int) $p['storage_location_id']);
        }
        if (! isset($p['warehouse_id']) || ! ($warehouse = Warehouse::find((int) $p['warehouse_id']))) {
            return null;
        }

        $roomId = isset($p['room_id']) && $p['room_id'] !== 'none' ? (int) $p['room_id'] : null;
        if (! isset($p['rack'])) {
            return StorageLocation::baseFor($warehouse->id, $roomId);
        }

        return StorageLocation::firstOrCreate([
            'warehouse_id' => $warehouse->id,
            'room_id' => $roomId,
            'rack' => $p['rack'],
            'shelf' => $p['shelf'] ?? null,
            'bin' => null,
        ]);
    }

    /** Czytelny opis miejsca, np. "Magazyn 1 › Hala › Regał R1" (null = brak miejsca). */
    public function label(): ?string
    {
        $p = $this->params;

        if (isset($p['storage_location_id'])) {
            return StorageLocation::with(['warehouse', 'room'])->find((int) $p['storage_location_id'])?->label();
        }
        if (! isset($p['warehouse_id']) || ! ($warehouse = Warehouse::find((int) $p['warehouse_id']))) {
            return null;
        }

        $parts = [$warehouse->name];
        if (($p['room_id'] ?? null) === 'none') {
            $parts[] = __('no room');
        } elseif (isset($p['room_id'])) {
            $parts[] = Room::find((int) $p['room_id'])?->name;
        }
        if (isset($p['rack'])) {
            $parts[] = __('Rack').' '.$p['rack'];
        }
        if (isset($p['shelf'])) {
            $parts[] = __('Shelf').' '.$p['shelf'];
        }

        return implode(' › ', array_filter($parts));
    }

    public function warehouseId(): ?int
    {
        if (isset($this->params['storage_location_id'])) {
            return StorageLocation::find((int) $this->params['storage_location_id'])?->warehouse_id;
        }

        return isset($this->params['warehouse_id']) ? (int) $this->params['warehouse_id'] : null;
    }
}
