<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleListing extends Model
{
    protected $fillable = ['item_id', 'platform', 'title', 'description', 'price', 'status', 'exported_at'];

    protected $casts = [
        'price' => 'decimal:2',
        'exported_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
