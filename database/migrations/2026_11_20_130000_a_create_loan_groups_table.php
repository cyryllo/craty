<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zestaw: kilka przedmiotów wypożyczonych naraz jednej osobie, z jednym
 * protokołem. Każdy przedmiot ma dalej własny wiersz w loans (z
 * loan_group_id), więc zwrot pojedynczej rzeczy, przeterminowanie i
 * historia działają jak przy zwykłym wypożyczeniu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('borrower_name');
            $table->date('due_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('borrowed_at');
            $table->timestamps();
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->foreignId('loan_group_id')->nullable()->after('item_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('loan_group_id');
        });
        Schema::dropIfExists('loan_groups');
    }
};
