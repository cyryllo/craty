<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Rezerwacja czasu na przedmiocie stacjonarnym (moduł "Rezerwacje maszyn").
 * Ważna od razu po zapisaniu (decyzja właściciela), bez akceptacji obsługi;
 * nakładające się rezerwacje tej samej maszyny są odrzucane w kontrolerze.
 */
class MachineBooking extends Model
{
    protected $fillable = ['item_id', 'user_id', 'user_name', 'starts_at', 'ends_at', 'purpose', 'description'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    // Wartości to teksty źródłowe do __() (klucze angielskie), jak w Item::STATUSES.
    public const PURPOSES = [
        'prywatny' => 'Private use',
        'zajecia' => 'Classes',
        'warsztaty' => 'Workshops',
        'konserwacja' => 'Maintenance',
    ];

    /** Rodzaje, które może wybrać tylko obsługa (zablokowanie maszyny na konserwację). */
    public const STAFF_ONLY_PURPOSES = ['konserwacja'];

    /** Krok siatki czasu w minutach: od-do muszą być jego wielokrotnością. */
    public const STEP_MINUTES = 15;

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Rezerwacje zachodzące na przedział [start, end) (stykające się końcami nie kolidują). */
    public function scopeOverlapping(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->where('starts_at', '<', $end)->where('ends_at', '>', $start);
    }

    /** Jeszcze trwające albo przyszłe. */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('ends_at', '>', now());
    }

    /** Kolor rodzaju w grafiku i na listach (klasy Tailwinda, pełne nazwy, żeby trafiły do CSS). */
    public static function purposeClass(string $purpose): string
    {
        return match ($purpose) {
            'zajecia' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
            'warsztaty' => 'bg-amber-100 text-amber-900 dark:bg-amber-900 dark:text-amber-200',
            'konserwacja' => 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
            default => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
        };
    }

    public function purposeLabel(): string
    {
        return __(self::PURPOSES[$this->purpose] ?? $this->purpose);
    }

    public function isNow(): bool
    {
        return $this->starts_at->lte(now()) && $this->ends_at->gt(now());
    }

    /** Obsługa odwołuje każdą rezerwację, Użytkownik tylko swoją, która jeszcze się nie skończyła. */
    public function canBeCancelledBy(User $user): bool
    {
        if ($user->isMagazynier()) {
            return true;
        }

        return $this->user_id === $user->id && $this->ends_at->isFuture();
    }

    /** Godziny "10:00–12:30" (z datą, gdy rezerwacja przechodzi przez północ). */
    public function timeRange(): string
    {
        $end = $this->ends_at->isSameDay($this->starts_at) ? $this->ends_at->format('H:i') : $this->ends_at->format('d.m H:i');

        return $this->starts_at->format('H:i').'–'.$end;
    }
}
