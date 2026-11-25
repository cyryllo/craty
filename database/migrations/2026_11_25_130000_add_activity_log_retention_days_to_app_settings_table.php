<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ile dni trzymać wpisy dziennika aktywności (wybór z listy w Ustawienia →
 * Dziennik aktywności, ActivityLog::RETENTION_OPTIONS). 0 = bez limitu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('activity_log_retention_days')->default(365);
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn('activity_log_retention_days');
        });
    }
};
