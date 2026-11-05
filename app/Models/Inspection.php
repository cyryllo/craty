<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Moduł Serwis: wykonany przegląd okresowy przedmiotu. */
class Inspection extends Model
{
    protected $fillable = ['item_id', 'performed_at', 'passed', 'notes', 'user_id'];

    protected $casts = [
        'performed_at' => 'date',
        'passed' => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
