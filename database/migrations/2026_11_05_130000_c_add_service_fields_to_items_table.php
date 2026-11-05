<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moduł Serwis: koniec gwarancji, co ile miesięcy przegląd i termin
 * następnego (liczony sam po zapisaniu przeglądu, można też ustawić ręcznie).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->date('warranty_until')->nullable()->after('purchased_at');
            $table->unsignedSmallInteger('inspection_interval_months')->nullable()->after('warranty_until');
            $table->date('next_inspection_at')->nullable()->after('inspection_interval_months');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['warranty_until', 'inspection_interval_months', 'next_inspection_at']);
        });
    }
};
