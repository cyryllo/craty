<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Rezerwacja przedmiotu na przyszły termin (całe dni, starts_on..ends_on). */
class Reservation extends Model
{
    protected $fillable = ['item_id', 'contact_id', 'borrower_name', 'starts_on', 'ends_on', 'notes', 'created_by', 'loan_id'];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /** Jeszcze nie wydana i nie minęła. */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('loan_id')->whereDate('ends_on', '>=', today());
    }

    /** Termin zachodzi na podany przedział (całe dni, oba końce włącznie). */
    public function scopeOverlapping(Builder $query, $from, $to): void
    {
        $query->whereDate('starts_on', '<=', $to)->whereDate('ends_on', '>=', $from);
    }

    public function borrowerLabel(): string
    {
        return $this->contact?->name ?? $this->borrower_name;
    }

    /** Trwa dzisiaj (do wydania teraz). */
    public function isCurrent(): bool
    {
        return $this->starts_on->lte(today()) && $this->ends_on->gte(today());
    }
}
