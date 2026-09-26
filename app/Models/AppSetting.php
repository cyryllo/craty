<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
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
        'public_marketplace_enabled', 'public_contact_email', 'public_contact_phone',
        'module_sales_enabled', 'module_locations_enabled',
        'password_reset_enabled', 'loan_due_notifications_enabled',
    ];

    protected $casts = [
        // Jedyne miejsce w appce, gdzie w bazie ląduje sekret tego typu —
        // reszta AppSetting to jawne, nieszyfrowane dane (nazwa, logo...).
        'mail_password' => 'encrypted',
        'public_marketplace_enabled' => 'boolean',
        'module_sales_enabled' => 'boolean',
        'module_locations_enabled' => 'boolean',
        'password_reset_enabled' => 'boolean',
        'loan_due_notifications_enabled' => 'boolean',
    ];

    // Tak jak w User (rola/active) — bez tego świeży, jeszcze niezapisany
    // firstOrNew() (patrz current()) miałby te pola jako null zamiast
    // wartości domyślnej z migracji, dopóki ktoś raz nie zapisałby ustawień.
    protected $attributes = [
        'public_marketplace_enabled' => false,
        'module_sales_enabled' => true,
        'module_locations_enabled' => false,
        'password_reset_enabled' => true,
        'loan_due_notifications_enabled' => false,
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

    /**
     * Wołane z x-application-logo, więc pojawia się na КAŻDEJ stronie —
     * łącznie z Instalatorem, uruchamianym zanim `app_settings` (albo
     * cokolwiek innego) w ogóle istnieje w bazie. Bez tej osłony każde
     * wejście na świeżą instalację wywalałoby się na SQLSTATE 42S02,
     * jeszcze zanim ktokolwiek zdążył wypełnić krok 2 kreatora.
     */
    public static function current(): self
    {
        try {
            return Schema::hasTable('app_settings') ? static::firstOrNew(['id' => 1]) : new static;
        } catch (\Throwable) {
            return new static;
        }
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
