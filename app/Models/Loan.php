<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    protected $fillable = [
        'item_id', 'borrowed_by', 'borrower_name', 'contact_id', 'issued_by', 'condition_out',
        'borrowed_at', 'due_at', 'returned_at', 'returned_to', 'condition_in', 'return_notes', 'notes', 'loan_group_id',
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

    /** Zestaw, do którego należy to wypożyczenie (null = pojedyncze). */
    public function group(): BelongsTo
    {
        return $this->belongsTo(LoanGroup::class, 'loan_group_id');
    }

    /**
     * Wypożyczenie jednego przedmiotu (pojedynczo, w zestawie albo z
     * rezerwacji): zapis stanu przy wydaniu, status "wypożyczony" i wpis w
     * historii z nazwą wypożyczającego (sama zmiana statusu nie mówi, komu).
     *
     * @param  array<string, mixed>  $attributes  due_at, notes, loan_group_id
     */
    public static function lend(Item $item, ?Contact $contact, string $borrowerName, User $by, array $attributes = []): self
    {
        $loan = $item->loans()->create([
            ...$attributes,
            'borrower_name' => $borrowerName,
            'contact_id' => $contact?->id,
            'issued_by' => $by->id,
            'condition_out' => $item->condition,
            'borrowed_at' => now(),
        ]);

        $item->update(['status' => 'wypozyczony']);

        $item->histories()->create([
            'user_id' => $by->id,
            'action' => 'loaned',
            'new_value' => $loan->borrowerLabel(),
            'old_value' => $loan->due_at?->format('d.m.Y'),
        ]);

        return $loan;
    }

    /**
     * Zwrot: stan przy zwrocie i uwagi (do protokołu), status "dostępny" i
     * nowy stan w kartotece przedmiotu, jeśli się zmienił (ItemObserver
     * zapisze to w historii).
     */
    public function registerReturn(User $by, ?string $condition = null, ?string $notes = null): void
    {
        $this->update([
            'returned_at' => now(),
            'returned_to' => $by->id,
            'condition_in' => $condition,
            'return_notes' => $notes,
        ]);

        $this->item->update(array_filter([
            'status' => 'dostepny',
            'condition' => $condition,
        ]));

        $this->item->histories()->create([
            'user_id' => $by->id,
            'action' => 'returned',
            'old_value' => $this->borrowerLabel(),
        ]);
    }

    /** Protokół wydania: przy rzeczy z zestawu wspólny protokół całego zestawu. */
    public function issueProtocolUrl(): string
    {
        return $this->loan_group_id
            ? route('loan-groups.protocol.issue', $this->loan_group_id)
            : route('loans.protocol.issue', $this);
    }

    public function returnProtocolUrl(): string
    {
        return $this->loan_group_id
            ? route('loan-groups.protocol.return', $this->loan_group_id)
            : route('loans.protocol.return', $this);
    }
}
