<?php

namespace App\Services;

use App\Models\Category;
use App\Models\StorageLocation;
use Illuminate\Support\Facades\DB;

/**
 * Buduje numer ewidencyjny w formacie:
 *
 *   {KATEGORIA}-{LOKALIZACJA}-{ROK}-{NUMER}
 *   np. NAR-M1R3-2026-00042
 *
 * Segment kolejnego numeru jest pobierany atomowo z tabeli
 * inventory_number_sequences (jeden licznik na rok), żeby dwa równoczesne
 * zgłoszenia przedmiotu nigdy nie dostały tego samego numeru.
 */
class InventoryNumberGenerator
{
    public function generate(?Category $category, ?StorageLocation $location): string
    {
        $categoryCode = $category?->code ?? 'GEN';
        $locationCode = $location ? str_replace('-', '', $location->code) : 'BRAK';
        $year = (int) now()->format('Y');
        $sequence = $this->nextSequenceFor($year);

        return sprintf('%s-%s-%d-%05d', $categoryCode, $locationCode, $year, $sequence);
    }

    private function nextSequenceFor(int $year): int
    {
        return DB::transaction(function () use ($year) {
            $row = DB::table('inventory_number_sequences')
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                DB::table('inventory_number_sequences')->insert(['year' => $year, 'next_number' => 2]);

                return 1;
            }

            DB::table('inventory_number_sequences')
                ->where('year', $year)
                ->update(['next_number' => $row->next_number + 1]);

            return $row->next_number;
        });
    }
}
