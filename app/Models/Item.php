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
        'inventory_no', 'name', 'description', 'specification', 'value',
        'purchased_at', 'condition', 'status', 'category_id',
        'storage_location_id', 'created_by', 'qr_path',
    ];

    protected $casts = [
        'purchased_at' => 'date',
        'value' => 'decimal:2',
    ];

    public const STATUSES = [
        'dostepny' => 'Dostępny',
        'wypozyczony' => 'Wypożyczony',
        'w_naprawie' => 'W naprawie',
        'do_sprzedazy' => 'Do sprzedaży',
        'sprzedany' => 'Sprzedany',
        'wycofany' => 'Wycofany',
    ];

    public const CONDITIONS = [
        'nowy' => 'Nowy',
        'uzywany' => 'Używany',
        'uszkodzony' => 'Uszkodzony',
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

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
