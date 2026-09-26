<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleListing extends Model
{
    protected $fillable = ['item_id', 'platform', 'title', 'description', 'price', 'external_url', 'status', 'exported_at'];

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
        'wycofana' => 'Withdrawn',
    ];

    /** Wartości to teksty źródłowe do __() — nazwy własne (OLX, Allegro) i tak się nie tłumaczą. */
    public const PLATFORMS = [
        'olx' => 'OLX',
        'allegro' => 'Allegro',
        'inne' => 'Other',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Nazwa platformy do etykiety przycisku na pchlim targu — rozpoznawana
     * z domeny samego linku, nie z pola `platform` (ktoś mógł wybrać "OLX",
     * a wkleić link do Allegro, albo odwrotnie).
     */
    public function externalPlatformName(): ?string
    {
        $host = strtolower((string) parse_url((string) $this->external_url, PHP_URL_HOST));

        return match (true) {
            $host === '' => null,
            str_contains($host, 'olx.') => 'OLX',
            str_contains($host, 'allegro.') => 'Allegro',
            default => null,
        };
    }

    public function statusLabel(): string
    {
        return __(self::STATUSES[$this->status] ?? $this->status);
    }
}
