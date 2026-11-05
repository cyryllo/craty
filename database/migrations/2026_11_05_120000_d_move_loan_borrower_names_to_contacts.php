<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Zamienia dotychczasowe "komu wypożyczono" (wolny tekst w
 * loans.borrower_name) na kontakty: każda unikalna nazwa (bez względu na
 * wielkość liter i spacje na brzegach) staje się jednym kontaktem typu
 * "osoba", a jej wypożyczenia są do niego podpinane. Same teksty w
 * borrower_name zostają bez zmian. Osobna migracja (nie w tej z kolumnami),
 * żeby dało się ją przetestować i bezpiecznie ponowić: rusza tylko
 * wypożyczenia, które jeszcze nie mają kontaktu.
 */
return new class extends Migration
{
    public function up(): void
    {
        $loans = DB::table('loans')
            ->whereNull('contact_id')
            ->whereNotNull('borrower_name')
            ->orderBy('id')
            ->get(['id', 'borrower_name']);

        $contactIds = [];

        foreach ($loans as $loan) {
            $name = trim($loan->borrower_name);
            if ($name === '') {
                continue;
            }

            $key = mb_strtolower($name);
            if (! isset($contactIds[$key])) {
                $existing = DB::table('contacts')->whereRaw('LOWER(name) = ?', [$key])->value('id');
                $contactIds[$key] = $existing ?? DB::table('contacts')->insertGetId([
                    'name' => $name,
                    'type' => 'osoba',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('loans')->where('id', $loan->id)->update(['contact_id' => $contactIds[$key]]);
        }
    }

    public function down(): void
    {
        // Kontaktów nie usuwamy: mogły zostać od tego czasu uzupełnione.
    }
};
