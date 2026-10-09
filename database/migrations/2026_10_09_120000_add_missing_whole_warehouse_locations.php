<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Uzupełnia lokalizację "cały magazyn" (bez pomieszczenia i regału/półki/
 * pojemnika) każdemu magazynowi, który jej nie ma. Poprzednia migracja
 * (add_base_location_for_warehouses_without_one) sprawdzała tylko puste
 * rack/shelf/bin, nie room_id — a dane demo zakładały magazyn bez bazowej
 * lokalizacji wcale — więc część magazynów mogła jej nie dostać. Tam trafiają
 * przedmioty z usuwanego pomieszczenia, więc musi istnieć zawsze.
 *
 * Raw DB::table(), nie modele — patrz uzasadnienie we wspomnianej migracji.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('warehouses')->get(['id', 'code']) as $warehouse) {
            $hasBase = DB::table('storage_locations')
                ->where('warehouse_id', $warehouse->id)
                ->whereNull('room_id')
                ->whereNull('rack')->whereNull('shelf')->whereNull('bin')
                ->exists();

            // storage_locations.code jest unikalny — gdyby kod magazynu był
            // już czymś zajęty, nie wywracamy aktualizacji; brak zostanie
            // uzupełniony w locie (StorageLocation::baseFor()) przy pierwszej potrzebie.
            if (! $hasBase && ! DB::table('storage_locations')->where('code', $warehouse->code)->exists()) {
                DB::table('storage_locations')->insert([
                    'warehouse_id' => $warehouse->id,
                    'code' => $warehouse->code,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Celowo nic — do tej lokalizacji mogły już trafić przedmioty.
    }
};
