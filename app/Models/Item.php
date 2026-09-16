<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_no', 'name', 'serial_number', 'ean', 'description', 'value',
        'purchased_at', 'condition', 'status', 'category_id',
        'storage_location_id', 'created_by', 'qr_path',
        // 'needs_completion' celowo pominięte — ustawia je tylko
        // ScanController::quickAddStore() (przez forceFill), nigdy zwykły
        // formularz, tak samo jak 'protected' na User.
    ];

    protected $casts = [
        'purchased_at' => 'date',
        'value' => 'decimal:2',
        'needs_completion' => 'boolean',
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

    /**
     * Wszystkie zdjęcia, ale z tym oznaczonym jako główne zawsze na
     * początku — pchli targ (patrz TODO.md "Drobne rzeczy zauważone przy
     * budowie") pokazuje teraz galerię zamiast samego primaryPhoto, więc
     * potrzebuje ustalonej kolejności "okładka, potem reszta" zamiast
     * gołego porządku po sort_order (który primaryPhoto akurat dziś
     * respektuje, bo jest ustawiane tylko na pierwsze dodane zdjęcie —
     * ale nie ma gwarancji, że tak zostanie po przyszłych zmianach).
     */
    public function photosForGallery(): Collection
    {
        return $this->photos->sortByDesc('is_primary')->values();
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
