<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            // Domyślnie WŁĄCZONY (odwrotnie niż np. public_marketplace_enabled)
            // — Sprzedaż to już istniejąca, używana funkcja; wyłączenie jej po
            // aktualizacji zaskoczyłoby istniejące instalacje. Pierwszy z
            // planowanych kilku modułów włącz/wyłącz — patrz App\Support\Modules.
            $table->boolean('module_sales_enabled')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn('module_sales_enabled');
        });
    }
};
