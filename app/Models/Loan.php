<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    protected $fillable = [
        'item_id', 'borrowed_by', 'borrower_name', 'contact_id', 'issued_by', 'condition_out',
        'borrowed_at', 'due_at', 'returned_at', 'returned_to', 'condition_in', 'return_notes', 'notes',
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

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /** Kto z obsługi wydał przedmiot. */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /** Kto z obsługi przyjął zwrot. */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_to');
    }

    /**
     * Kontakt ma pierwszeństwo (aktualna nazwa), potem konto w appce, a na
     * końcu nazwa zapisana przy wypożyczeniu: zostaje nawet po usunięciu
     * kontaktu albo wyłączeniu modułu Kontakty.
     */
    public function borrowerLabel(): string
    {
        return $this->contact?->name ?? $this->borrower?->name ?? $this->borrower_name ?? '—';
    }

    public function isOverdue(): bool
    {
        return is_null($this->returned_at) && $this->due_at && $this->due_at->isPast();
    }

    /** Numer protokołu, np. "12/2026": id wypożyczenia i rok wydania. */
    public function protocolNumber(): string
    {
        return $this->id.'/'.$this->borrowed_at->format('Y');
    }

    public static function conditionLabelFor(?string $condition): ?string
    {
        return $condition ? __(Item::CONDITIONS[$condition] ?? $condition) : null;
    }
}
