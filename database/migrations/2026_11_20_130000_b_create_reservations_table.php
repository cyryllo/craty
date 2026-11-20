<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rezerwacja przedmiotu na przyszły termin (starts_on..ends_on, całe dni).
 * Aktywna, dopóki nie została wydana (loan_id) i nie minęła. Odwołanie
 * po prostu usuwa wiersz. "Komu" tak jak w wypożyczeniu: kontakt plus
 * nazwa zapisana w chwili rezerwacji.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('borrower_name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('loan_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['item_id', 'starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
