<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Licznik numerów ewidencyjnych w rozbiciu na rok — po jednym wierszu na rok,
     * inkrementowany atomowo (SELECT ... FOR UPDATE) przez InventoryNumberGenerator,
     * żeby dwa równoczesne zgłoszenia nie dostały tego samego numeru kolejnego.
     */
    public function up(): void
    {
        Schema::create('inventory_number_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('next_number')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_number_sequences');
    }
};
