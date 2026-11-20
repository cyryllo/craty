<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Etap v1.4: cotygodniowe przypomnienia z modułu Serwis (Ustawienia →
 * Powiadomienia) i ikona aplikacji z własnego logo (Ustawienia aplikacji).
 * Oba domyślnie wyłączone: po aktualizacji nic się samo nie zmienia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->boolean('service_reminders_enabled')->default(false);
            $table->boolean('logo_as_app_icon')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn(['service_reminders_enabled', 'logo_as_app_icon']);
        });
    }
};
