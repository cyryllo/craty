<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Opis" i "Specyfikacja techniczna" to na życzenie użytkownika teraz jedno
 * pole — dwa osobne pola robiły to samo (wolny tekst o przedmiocie), a przy
 * przygotowywaniu opisu do sprzedaży trzeba było ręcznie kopiować z obu.
 * Przed usunięciem kolumny doklejamy jej zawartość do description, żeby nie
 * zgubić danych już wpisanych w istniejące przedmioty.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('items')
            ->whereNotNull('specification')
            ->where('specification', '!=', '')
            ->get(['id', 'description', 'specification'])
            ->each(function ($item) {
                $merged = trim(trim((string) $item->description)."\n\n".$item->specification);
                DB::table('items')->where('id', $item->id)->update(['description' => $merged]);
            });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('specification');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Sama kolumna wraca, ale bez oryginalnego podziału treści —
            // to, co zostało dołączone do description przy up(), tam zostaje.
            $table->text('specification')->nullable()->after('description');
        });
    }
};
