<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    protected $fillable = [
        'item_id', 'borrowed_by', 'borrower_name', 'borrowed_at', 'due_at', 'returned_at', 'notes',
    ];

    protected $casts = [
        'borrowed_at' => 'datetime',
        'due_at' => 'date',
        'returned_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrowed_by');
    }

    public function borrowerLabel(): string
    {
        return $this->borrower?->name ?? $this->borrower_name ?? '—';
    }

    public function isOverdue(): bool
    {
        return is_null($this->returned_at) && $this->due_at && $this->due_at->isPast();
    }
}
