<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/** Moduł Materiały eksploatacyjne: rzecz liczona ilością, z minimum i historią ruchów. */
class Consumable extends Model
{
    protected $fillable = ['name', 'category_id', 'storage_location_id', 'unit', 'min_quantity', 'notes'];

    protected $casts = [
        'quantity' => 'decimal:3',
        'min_quantity' => 'decimal:3',
    ];

    protected $attributes = [
        'unit' => 'szt',
        'quantity' => 0,
    ];

    // Wartości to teksty źródłowe do __() (klucze angielskie), jak w Item::STATUSES.
    public const UNITS = [
        'szt' => 'pcs',
        'opak' => 'pack',
        'm' => 'm',
        'l' => 'l',
        'kg' => 'kg',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ConsumableMovement::class)->latest('id');
    }

    /** Ilość na minimum albo poniżej (bez ustawionego minimum: nigdy). */
    public function scopeLow(Builder $query): void
    {
        $query->whereNotNull('min_quantity')->whereColumn('quantity', '<=', 'min_quantity');
    }

    public function isLow(): bool
    {
        return $this->min_quantity !== null && (float) $this->quantity <= (float) $this->min_quantity;
    }

    public function unitLabel(): string
    {
        return __(self::UNITS[$this->unit] ?? $this->unit);
    }

    /** Ilość bez zbędnych zer po przecinku: "12", "2,5", "0,125". */
    public static function formatQuantity($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 3, ',', ' '), '0'), ',');
    }

    /**
     * Zmiana ilości (+ uzupełnienie, − pobranie) z wpisem w historii. W
     * transakcji z blokadą wiersza, żeby dwa jednoczesne pobrania nie
     * nadpisały sobie wyniku. Ilość nie schodzi poniżej zera.
     */
    public function adjust(float $change, ?string $note, User $by): ConsumableMovement
    {
        return DB::transaction(function () use ($change, $note, $by) {
            $fresh = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            $after = max(0, round((float) $fresh->quantity + $change, 3));
            $applied = round($after - (float) $fresh->quantity, 3);

            $fresh->forceFill(['quantity' => $after])->save();
            $this->setRawAttributes($fresh->getAttributes(), true);

            return $this->movements()->create([
                'change' => $applied,
                'quantity_after' => $after,
                'note' => $note,
                'user_id' => $by->id,
            ]);
        });
    }
}
