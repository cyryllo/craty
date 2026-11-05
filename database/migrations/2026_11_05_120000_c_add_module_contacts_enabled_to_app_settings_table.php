<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            // Domyślnie włączony (decyzja właściciela, 2026-11-05): działa od
            // razu po aktualizacji, kto nie chce, wyłącza w Ustawieniach → Moduły.
            $table->boolean('module_contacts_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn('module_contacts_enabled');
        });
    }
};
