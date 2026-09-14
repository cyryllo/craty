<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Ustawienia wyglądu i infrastruktury appki trzymane jako pojedynczy wiersz
 * (id=1) zamiast osobnej tabeli klucz-wartość — mamy tylko kilkanaście pól,
 * więc to prostsze.
 */
class AppSetting extends Model
{
    protected $fillable = [
        'name', 'logo_path', 'locale',
        'mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_password',
        'mail_from_address', 'mail_from_name',
        'backup_retention_days', 'backup_include_env',
        'public_marketplace_enabled', 'public_contact_email', 'public_contact_phone',
    ];

    protected $casts = [
        // Jedyne miejsce w appce, gdzie w bazie ląduje sekret tego typu —
        // reszta AppSetting to jawne, nieszyfrowane dane (nazwa, logo...).
        'mail_password' => 'encrypted',
        'backup_include_env' => 'boolean',
        'public_marketplace_enabled' => 'boolean',
    ];

    // Tak jak w User (rola/active) — bez tego świeży, jeszcze niezapisany
    // firstOrNew() (patrz current()) miałby te pola jako null zamiast
    // wartości domyślnej z migracji, dopóki ktoś raz nie zapisałby ustawień.
    protected $attributes = [
        'backup_retention_days' => 14,
        'backup_include_env' => false,
        'public_marketplace_enabled' => false,
    ];

    public const LOCALES = [
        'en' => 'English',
        'pl' => 'Polski',
    ];

    // Wartość '' to tekst źródłowy do __() (klucz angielski), jak w Item::STATUSES.
    public const MAIL_ENCRYPTIONS = [
        '' => 'None',
        'tls' => 'TLS',
        'ssl' => 'SSL',
    ];

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

    /** Czy admin skonfigurował własną pocztę SMTP (zamiast polegać na .env). */
    public function hasCustomMailSettings(): bool
    {
        return filled($this->mail_host);
    }
}
