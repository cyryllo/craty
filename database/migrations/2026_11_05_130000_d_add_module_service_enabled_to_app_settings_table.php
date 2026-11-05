<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            // Domyślnie WYŁĄCZONY (decyzja właściciela, 2026-11-05): po
            // aktualizacji nic się nie zmienia, admin włącza go w Ustawienia → Moduły.
            $table->boolean('module_service_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn('module_service_enabled');
        });
    }
};
