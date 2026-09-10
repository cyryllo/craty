<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('rack')->nullable();   // regał
            $table->string('shelf')->nullable();  // półka
            $table->string('bin')->nullable();    // pojemnik / skrzynka
            $table->string('note')->nullable();
            // Złożony, czytelny kod np. "M1-R3-P2-K1", generowany z powyższych pól.
            $table->string('code')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_locations');
    }
};
