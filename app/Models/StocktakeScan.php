<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Przedmiot zeskanowany podczas inwentaryzacji (in_place = leżał tam, gdzie powinien). */
class StocktakeScan extends Model
{
    protected $fillable = ['stocktake_id', 'item_id', 'in_place', 'moved_at', 'scanned_by'];

    protected $casts = [
        'in_place' => 'boolean',
        'moved_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
