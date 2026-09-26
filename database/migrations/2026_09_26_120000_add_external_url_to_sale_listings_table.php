<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opcjonalny link do tej samej oferty wystawionej na OLX/Allegro — pokazywany
 * na publicznym pchlim targu jako przycisk, żeby zainteresowany mógł kupić
 * przez platformę zamiast pisać maila.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_listings', function (Blueprint $table) {
            $table->string('external_url', 2048)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('sale_listings', function (Blueprint $table) {
            $table->dropColumn('external_url');
        });
    }
};
