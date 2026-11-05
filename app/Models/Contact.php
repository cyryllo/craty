<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Moduł Kontakty: komu wypożyczamy sprzęt i dokąd wysyłamy go do naprawy.
 * Typ "serwis" czeka na przyszły moduł Serwis (TODO.md).
 */
class Contact extends Model
{
    protected $fillable = ['name', 'type', 'phone', 'email', 'address', 'notes'];

    protected $attributes = [
        'type' => 'osoba',
    ];

    // Wartości to teksty źródłowe do __() (klucze angielskie), jak w Item::STATUSES.
    public const TYPES = [
        'osoba' => 'Person',
        'firma' => 'Company',
        'serwis' => 'Repair service',
    ];

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function activeLoans(): HasMany
    {
        return $this->loans()->whereNull('returned_at');
    }

    public function typeLabel(): string
    {
        return __(self::TYPES[$this->type] ?? $this->type);
    }
}
