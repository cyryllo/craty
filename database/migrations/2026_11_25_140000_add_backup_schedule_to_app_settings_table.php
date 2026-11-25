<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kopie zapasowe z panelu (BackupService): automatyczna kopia off/daily/weekly.
 * Czas przechowywania kopii używa istniejącej kolumny backup_retention_days
 * (została po usuniętym w 2026-09 module kopii, domyślnie 14 dni).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->string('backup_schedule', 10)->default('off');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn('backup_schedule');
        });
    }
};
