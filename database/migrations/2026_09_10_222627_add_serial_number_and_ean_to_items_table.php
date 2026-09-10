<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Numer seryjny/fabryczny nadany przez producenta — odrębny od
            // naszego numeru ewidencyjnego (inventory_no). Oba pola opcjonalne,
            // bo nie każdy przedmiot (np. materiały) je ma.
            $table->string('serial_number')->nullable()->after('name');
            $table->string('ean', 32)->nullable()->after('serial_number');

            $table->index('serial_number');
            $table->index('ean');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['serial_number']);
            $table->dropIndex(['ean']);
            $table->dropColumn(['serial_number', 'ean']);
        });
    }
};
