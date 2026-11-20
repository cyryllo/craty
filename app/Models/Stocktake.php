<?php

namespace App\Models;

use App\Support\LocationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Inwentaryzacja jednego miejsca w magazynie. */
class Stocktake extends Model
{
    protected $fillable = ['scope', 'label', 'expected_item_ids', 'away_item_ids', 'started_by', 'finished_at', 'finished_by'];

    protected $casts = [
        'scope' => 'array',
        'expected_item_ids' => 'array',
        'away_item_ids' => 'array',
        'finished_at' => 'datetime',
    ];

    /** Statusy, przy których przedmiot jest poza magazynem i nie liczy się jako brak. */
    public const AWAY_STATUSES = ['wypozyczony', 'w_naprawie'];

    public function scans(): HasMany
    {
        return $this->hasMany(StocktakeScan::class)->latest('id');
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function locationScope(): LocationScope
    {
        return new LocationScope($this->scope);
    }

    public function isFinished(): bool
    {
        return $this->finished_at !== null;
    }

    /** Przedmioty, które według kartoteki są teraz w tym miejscu (w magazynie, nie sprzedane). */
    public function expectedItemsQuery(): Builder
    {
        return Item::inStock()->tap(fn ($q) => $this->locationScope()->apply($q));
    }
}
