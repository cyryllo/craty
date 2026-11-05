<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Moduł Serwis: naprawa przedmiotu (otwarta, dopóki returned_at jest puste). */
class Repair extends Model
{
    protected $fillable = [
        'item_id', 'contact_id', 'service_name', 'fault_description', 'sent_at', 'expected_at',
        'returned_at', 'cost', 'work_done', 'previous_status', 'created_by', 'closed_by',
    ];

    protected $casts = [
        'sent_at' => 'date',
        'expected_at' => 'date',
        'returned_at' => 'date',
        'cost' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /** Aktualna nazwa kontaktu, potem nazwa zapisana przy wysyłce; pusta = własny warsztat. */
    public function serviceLabel(): string
    {
        return $this->contact?->name ?? $this->service_name ?? __('In-house');
    }

    public function isOpen(): bool
    {
        return is_null($this->returned_at);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->expected_at && $this->expected_at->isPast() && ! $this->expected_at->isToday();
    }
}
