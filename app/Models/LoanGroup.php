<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Zestaw: kilka wypożyczeń (loans) wydanych naraz jednej osobie, z jednym protokołem. */
class LoanGroup extends Model
{
    protected $fillable = ['contact_id', 'borrower_name', 'due_at', 'notes', 'issued_by', 'borrowed_at'];

    protected $casts = [
        'due_at' => 'date',
        'borrowed_at' => 'datetime',
    ];

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class)->orderBy('id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function borrowerLabel(): string
    {
        return $this->contact?->name ?? $this->borrower_name;
    }

    /** Numer protokołu zestawu, np. "Z7/2026" (Z = zestaw; pojedyncze mają same cyfry). */
    public function protocolNumber(): string
    {
        return 'Z'.$this->id.'/'.$this->borrowed_at->format('Y');
    }

    public function isFullyReturned(): bool
    {
        return $this->loans->every(fn (Loan $loan) => $loan->returned_at !== null);
    }
}
