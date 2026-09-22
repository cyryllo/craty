<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Do tej pory przedmiot dało się przypisać tylko do konkretnej lokalizacji
 * (regał/półka/pojemnik) — magazyn bez choć jednej zdefiniowanej lokalizacji
 * był niewybieralny na formularzu przedmiotu, mimo że nie każdy chce aż tak
 * szczegółowo rozpisywać magazyn. StorageLocation już obsługiwał puste
 * rack/shelf/bin (StorageLocation::buildCode() zwraca wtedy sam kod
 * magazynu, np. "M1"), więc dokładamy po jednej takiej "bazowej" lokalizacji
 * dla każdego magazynu, który jeszcze żadnej nie ma — od teraz
 * WarehouseController::store() robi to samo dla nowo tworzonych magazynów.
 *
 * Raw DB::table(), nie modele Eloquent — migracje mają zostać poprawne przy
 * odtworzeniu od zera (świeży install, `migrate --force` z paczki
 * aktualizacji) niezależnie od tego, jak App\Models\Warehouse/StorageLocation
 * wyglądają w danym momencie w przyszłości.
 */
return new class extends Migration
{
    public function up(): void
    {
        $warehouses = DB::table('warehouses')->get(['id', 'code']);

        foreach ($warehouses as $warehouse) {
            $hasBaseLocation = DB::table('storage_locations')
                ->where('warehouse_id', $warehouse->id)
                ->whereNull('rack')
                ->whereNull('shelf')
                ->whereNull('bin')
                ->exists();

            if (! $hasBaseLocation) {
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
        // Nieodwracalne celowo — do czasu wykonania rollbacku te "bazowe"
        // lokalizacje mogły już dostać przypisane przedmioty; nie da się
        // bezpiecznie odróżnić, które wpisy dorobiła ta migracja, a które
        // istniały wcześniej (ktoś mógł ręcznie utworzyć lokalizację z
        // pustym rack/shelf/bin jeszcze przed tą zmianą).
    }
};
