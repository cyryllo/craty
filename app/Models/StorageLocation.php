<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageLocation extends Model
{
    protected $fillable = ['warehouse_id', 'rack', 'shelf', 'bin', 'note', 'code'];

    protected static function booted(): void
    {
        static::saving(function (StorageLocation $location) {
            $location->code = $location->buildCode();
        });
    }

    /**
     * Buduje czytelny kod lokalizacji, np. "M1-R3-P2-K1", ze skrótu magazynu
     * i wypełnionych segmentów regał/półka/pojemnik.
     */
    public function buildCode(): string
    {
        $warehouseCode = $this->warehouse?->code ?? Warehouse::find($this->warehouse_id)?->code ?? '?';

        $segments = array_filter([
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

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function label(): string
    {
        return $this->warehouse->name.' — '.$this->code;
    }
}
