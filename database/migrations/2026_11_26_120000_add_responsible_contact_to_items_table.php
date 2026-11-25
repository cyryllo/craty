<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Osoba odpowiedzialna za przedmiot (np. właściciel albo opiekun maszyny w
 * makerspace): kontakt z modułu Kontakty i rodzaj z Item::RESPONSIBLE_ROLES.
 * Rodzaj to zwykły string, nie enum, żeby dołożenie kolejnego nie wymagało
 * migracji.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->foreignId('responsible_contact_id')->nullable()->after('stationary')->constrained('contacts')->nullOnDelete();
            $table->string('responsible_role', 20)->nullable()->after('responsible_contact_id');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsible_contact_id');
            $table->dropColumn('responsible_role');
        });
    }
};
