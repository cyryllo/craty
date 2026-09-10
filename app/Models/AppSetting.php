<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Ustawienia wyglądu appki trzymane jako pojedynczy wiersz (id=1) zamiast
 * osobnej tabeli klucz-wartość — mamy tylko dwa pola, więc to prostsze.
 */
class AppSetting extends Model
{
    protected $fillable = ['name', 'logo_path'];

    public static function current(): self
    {
        return static::firstOrNew(['id' => 1]);
    }

    public function effectiveName(): string
    {
        return $this->name ?: config('app.name');
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
