<?php

namespace App\Models;

use App\Support\Modules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageLocation extends Model
{
    protected $fillable = ['warehouse_id', 'room_id', 'rack', 'shelf', 'bin', 'note', 'code'];

    protected static function booted(): void
    {
        static::saving(function (StorageLocation $location) {
            $location->code = $location->buildCode();
        });
    }

    /**
     * Buduje czytelny kod lokalizacji, np. "M1-HALA1-R3-P2-K1", ze skrótu
     * magazynu, opcjonalnego skrótu pomieszczenia i wypełnionych segmentów
     * regał/półka/pojemnik — każdy z tych segmentów jest opcjonalny, więc
     * kod może się skończyć na dowolnym poziomie szczegółowości (sam
     * magazyn, magazyn+pomieszczenie, albo pełna ścieżka).
     */
    public function buildCode(): string
    {
        $warehouseCode = $this->warehouse?->code ?? Warehouse::find($this->warehouse_id)?->code ?? '?';
        $roomCode = $this->room?->code ?? ($this->room_id ? Room::find($this->room_id)?->code : null);

        $segments = array_filter([
            $roomCode,
            $this->rack ? 'R'.$this->rack : null,
            $this->shelf ? 'P'.$this->shelf : null,
            $this->bin ? 'K'.$this->bin : null,
        ]);

        return trim($warehouseCode.'-'.implode('-', $segments), '-');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Lokalizacja bez pomieszczenia i bez regału/półki/pojemnika to "cały
     * magazyn, bez szczegółów" — patrz WarehouseController::store(), które
     * taką dokłada automatycznie do każdego nowego magazynu. Bez tego
     * dopisku wyglądałaby na liście identycznie jak zwykła, konkretna
     * lokalizacja (kod pokrywa się wtedy z kodem magazynu).
     */
    /** "Bazowa" lokalizacja = cały magazyn, bez pomieszczenia i regału/półki/pojemnika. */
    public function isBase(): bool
    {
        return ! $this->room_id && ! $this->rack && ! $this->shelf && ! $this->bin;
    }

    public function label(): string
    {
        if ($this->isBase()) {
            // Bez modułu "Rozszerzony magazyn" nie ma innych lokalizacji do
            // odróżnienia, więc wystarczy sama nazwa magazynu.
            return Modules::isEnabled('locations')
                ? $this->warehouse->name.' — '.__('whole warehouse, no specific spot')
                : $this->warehouse->name;
        }

        return $this->warehouse->name.' — '.$this->code;
    }
}
