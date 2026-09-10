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

    // Wartości to teksty źródłowe do __() (klucze angielskie) — "Completed"
    // zamiast "Sold", żeby nie kolidować w tłumaczeniach z Item::STATUSES
    // (po polsku różny rodzaj gramatyczny: "sprzedany" przedmiot vs
    // "sprzedana" oferta).
    public const STATUSES = [
        'szkic' => 'Drafted',
        'wyeksportowana' => 'Listed',
        'sprzedana' => 'Completed',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function statusLabel(): string
    {
        return __(self::STATUSES[$this->status] ?? $this->status);
    }
}
