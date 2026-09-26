<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moduł "Rozszerzony magazyn" (pomieszczenia + lokalizacje regał/półka/
 * pojemnik) — domyślnie WYŁĄCZONY, bo nie każdy rozpisuje magazyn aż tak
 * szczegółowo, a bez modułu przedmiot i tak trafia do "bazowej" lokalizacji
 * magazynu (patrz add_base_location_for_warehouses_without_one).
 *
 * Wyjątek: instalacja, która już używa pomieszczeń albo szczegółowych
 * lokalizacji, dostaje moduł włączony — aktualizacja nie może nikomu nagle
 * ukryć czegoś, czego już używał (ta sama zasada co module_sales_enabled).
 * Raw DB::table(), nie modele — patrz uzasadnienie we wspomnianej migracji.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->boolean('module_locations_enabled')->default(false);
        });

        $alreadyUsed = DB::table('rooms')->exists()
            || DB::table('storage_locations')
                ->where(fn ($q) => $q->whereNotNull('room_id')->orWhereNotNull('rack')->orWhereNotNull('shelf')->orWhereNotNull('bin'))
                ->exists();

        if (! $alreadyUsed) {
            return;
        }

        if (DB::table('app_settings')->exists()) {
            DB::table('app_settings')->update(['module_locations_enabled' => true]);
        } else {
            DB::table('app_settings')->insert(['module_locations_enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn('module_locations_enabled');
        });
    }
};
