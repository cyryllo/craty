<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // Krótki symbol pomieszczenia w obrębie magazynu, np. "HALA1" —
            // używany jako segment kodu lokalizacji (StorageLocation::buildCode()),
            // tak jak warehouses.code. Unikalny tylko w obrębie magazynu, nie globalnie
            // (patrz warehouses.code, które jest globalne — pomieszczenie nie jest
            // samodzielnym bytem poza swoim magazynem, więc "HALA1" w dwóch różnych
            // magazynach nie koliduje).
            $table->string('code', 16);
            $table->timestamps();

            $table->unique(['warehouse_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
