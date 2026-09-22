<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opcjonalny poziom hierarchii między magazynem i regałem/półką/pojemnikiem
 * — magazyn może mieć więcej niż jedno pomieszczenie (patrz "rooms" wyżej).
 * Nullable, bo lokalizacja nadal może pomijać pomieszczenie całkowicie
 * (dokładnie tak samo jak rack/shelf/bin już są opcjonalne) — nie każdy chce
 * rozpisywać magazyn aż tak szczegółowo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storage_locations', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->after('warehouse_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('storage_locations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_id');
        });
    }
};
