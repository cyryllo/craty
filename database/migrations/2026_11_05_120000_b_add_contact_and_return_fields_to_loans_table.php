<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pola pod Kontakty i protokoły wydania/zwrotu:
 * - contact_id: komu wypożyczono (borrower_name zostaje jako zapis nazwy z
 *   chwili wypożyczenia, więc usunięcie kontaktu albo wyłączenie modułu
 *   niczego nie gubi),
 * - issued_by / returned_to: kto z obsługi wydał i kto przyjął zwrot,
 * - condition_out / condition_in / return_notes: stan przy wydaniu i zwrocie
 *   plus uwagi, porównywane na protokole zwrotu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->foreignId('contact_id')->nullable()->after('borrower_name')->constrained()->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->after('contact_id')->constrained('users')->nullOnDelete();
            $table->foreignId('returned_to')->nullable()->after('returned_at')->constrained('users')->nullOnDelete();
            $table->string('condition_out', 20)->nullable()->after('issued_by');
            $table->string('condition_in', 20)->nullable()->after('returned_to');
            $table->text('return_notes')->nullable()->after('condition_in');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contact_id');
            $table->dropConstrainedForeignId('issued_by');
            $table->dropConstrainedForeignId('returned_to');
            $table->dropColumn(['condition_out', 'condition_in', 'return_notes']);
        });
    }
};
