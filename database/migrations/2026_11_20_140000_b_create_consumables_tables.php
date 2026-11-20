<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moduł Materiały eksploatacyjne: rzeczy liczone ilością (śrubki, klej,
 * kabel), osobno od przedmiotów (decyzja właściciela: nie mieszają się z
 * narzędziami, nie mają numerów ewidencyjnych ani wypożyczeń). Każda zmiana
 * ilości to wpis w consumable_movements, więc widać, kto i kiedy pobrał.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('storage_location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unit', 10)->default('szt');
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('min_quantity', 12, 3)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('consumable_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumable_id')->constrained()->cascadeOnDelete();
            $table->decimal('change', 12, 3);
            $table->decimal('quantity_after', 12, 3);
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('app_settings', function (Blueprint $table) {
            // Domyślnie wyłączony: nowy moduł, po aktualizacji nic się nie zmienia.
            $table->boolean('module_consumables_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn('module_consumables_enabled');
        });
        Schema::dropIfExists('consumable_movements');
        Schema::dropIfExists('consumables');
    }
};
