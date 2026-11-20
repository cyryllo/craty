<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inwentaryzacja: zakres miejsca (parametry App\Support\LocationScope),
 * zeskanowane przedmioty i migawka przy zakończeniu (expected_item_ids =
 * co powinno tam być, away_item_ids = z tego wypożyczone albo w naprawie),
 * żeby raport nie zmieniał się, gdy później ktoś coś przeniesie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocktakes', function (Blueprint $table) {
            $table->id();
            $table->json('scope');
            $table->string('label');
            $table->json('expected_item_ids')->nullable();
            $table->json('away_item_ids')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('finished_at')->nullable();
            $table->foreignId('finished_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stocktake_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stocktake_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->boolean('in_place');
            $table->dateTime('moved_at')->nullable();
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['stocktake_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocktake_scans');
        Schema::dropIfExists('stocktakes');
    }
};
