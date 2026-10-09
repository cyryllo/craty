<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleListing extends Model
{
    protected $fillable = ['item_id', 'title', 'description', 'price', 'external_url', 'status', 'exported_at'];

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


    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Nazwa serwisu rozpoznana z domeny linku do ogłoszenia, np. na przycisku
     * "Zobacz na …" na pchlim targu, w tabelach Sprzedaży i w eksporcie CSV.
     * Osobnego pola "platforma" w formularzu już nie ma (2026-10-09): link
     * sam mówi, gdzie jest ogłoszenie. Null = brak linku albo nieznany
     * serwis (ogólne "Zobacz ofertę").
     */
    public function externalPlatformName(): ?string
    {
        if (blank($this->external_url)) {
            return null;
        }

        $host = strtolower((string) parse_url((string) $this->external_url, PHP_URL_HOST));

        return match (true) {
            str_contains($host, 'allegrolokalnie.') => 'Allegro Lokalnie',
            str_contains($host, 'olx.') => 'OLX',
            str_contains($host, 'allegro.') => 'Allegro',
            str_contains($host, 'vinted.') => 'Vinted',
            default => null,
        };
    }

    public function statusLabel(): string
    {
        return __(self::STATUSES[$this->status] ?? $this->status);
    }
}
