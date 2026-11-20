<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Jedna zmiana ilości materiału (dodatnia = uzupełnienie, ujemna = pobranie). */
class ConsumableMovement extends Model
{
    protected $fillable = ['consumable_id', 'change', 'quantity_after', 'note', 'user_id'];

    protected $casts = [
        'change' => 'decimal:3',
        'quantity_after' => 'decimal:3',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
