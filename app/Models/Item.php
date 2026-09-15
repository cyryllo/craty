<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_no', 'name', 'serial_number', 'ean', 'description', 'specification', 'value',
        'purchased_at', 'condition', 'status', 'category_id',
        'storage_location_id', 'created_by', 'qr_path',
    ];

    protected $casts = [
        'purchased_at' => 'date',
        'value' => 'decimal:2',
    ];

    // Wartości to teksty źródłowe do __() (klucze angielskie) — patrz statusLabel()/conditionLabel().
    public const STATUSES = [
        'dostepny' => 'Available',
        'wypozyczony' => 'On loan',
        'w_naprawie' => 'Under repair',
        'do_sprzedazy' => 'For sale',
        'sprzedany' => 'Sold',
        'wycofany' => 'Retired',
    ];

    public const CONDITIONS = [
        'nowy' => 'New',
        'uzywany' => 'Used',
        'uszkodzony' => 'Damaged',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ItemPhoto::class)->orderBy('sort_order');
    }

    public function primaryPhoto(): HasMany
    {
        return $this->photos()->where('is_primary', true);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ItemAttachment::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ItemHistory::class)->latest();
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function currentLoan()
    {
        return $this->hasOne(Loan::class)->whereNull('returned_at')->latestOfMany();
    }

    public function saleListings(): HasMany
    {
        return $this->hasMany(SaleListing::class);
    }

    /** Oferta aktualnie wystawiona na sprzedaż (jeśli jest) — do przycisków "wycofaj"/"oznacz jako sprzedane" na karcie przedmiotu. */
    public function activeSaleListing()
    {
        return $this->hasOne(SaleListing::class)->where('status', 'wyeksportowana')->latestOfMany();
    }

    /** Oferta przygotowana, ale jeszcze nie wystawiona (szkic) — do przycisków "wystaw do sprzedaży"/"wycofaj" na karcie przedmiotu. */
    public function draftSaleListing()
    {
        return $this->hasOne(SaleListing::class)->where('status', 'szkic')->latestOfMany();
    }

    public function statusLabel(): string
    {
        return __(self::STATUSES[$this->status] ?? $this->status);
    }

    public function conditionLabel(): string
    {
        return __(self::CONDITIONS[$this->condition] ?? $this->condition);
    }
}
