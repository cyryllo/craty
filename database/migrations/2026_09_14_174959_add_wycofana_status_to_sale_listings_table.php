<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL/MariaDB: ENUM to prawdziwy typ, zmieniamy go surowym SQL-em
        // (Schema::table()->change() na ENUM-ie jest zawodne bez doctrine/dbal).
        // SQLite (testy): Laravel realizuje enum jako CHECK — tu change()
        // działa natywnie od Laravela 11, bez doctrine/dbal.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('sale_listings', function (Blueprint $table) {
                $table->enum('status', ['szkic', 'wyeksportowana', 'sprzedana', 'wycofana'])->default('szkic')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE sale_listings MODIFY COLUMN status ENUM('szkic', 'wyeksportowana', 'sprzedana', 'wycofana') NOT NULL DEFAULT 'szkic'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('sale_listings', function (Blueprint $table) {
                $table->enum('status', ['szkic', 'wyeksportowana', 'sprzedana'])->default('szkic')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE sale_listings MODIFY COLUMN status ENUM('szkic', 'wyeksportowana', 'sprzedana') NOT NULL DEFAULT 'szkic'");
    }
};
