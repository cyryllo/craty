<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vinted jako trzecia platforma sprzedaży obok OLX/Allegro. Ten sam wzorzec
 * co add_wycofana_status_to_sale_listings_table: na MySQL/MariaDB ENUM
 * zmieniamy surowym SQL-em, na SQLite (testy) wystarcza change().
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->setPlatforms(['olx', 'allegro', 'vinted', 'inne']);
    }

    public function down(): void
    {
        DB::table('sale_listings')->where('platform', 'vinted')->update(['platform' => 'inne']);

        $this->setPlatforms(['olx', 'allegro', 'inne']);
    }

    /** @param  array<int, string>  $platforms */
    private function setPlatforms(array $platforms): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('sale_listings', function (Blueprint $table) use ($platforms) {
                $table->enum('platform', $platforms)->default('olx')->change();
            });

            return;
        }

        $values = implode(', ', array_map(fn ($p) => "'{$p}'", $platforms));
        DB::statement("ALTER TABLE sale_listings MODIFY COLUMN platform ENUM({$values}) NOT NULL DEFAULT 'olx'");
    }
};
