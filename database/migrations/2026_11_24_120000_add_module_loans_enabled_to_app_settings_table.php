<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wypożyczenia (z zestawami, rezerwacjami na dni i protokołami) jako moduł do
 * wyłączenia. Domyślnie włączony, jak Sprzedaż: istniejące instalacje już
 * z nich korzystają i po aktualizacji nic nie mogą stracić.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->boolean('module_loans_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn('module_loans_enabled');
        });
    }
};
