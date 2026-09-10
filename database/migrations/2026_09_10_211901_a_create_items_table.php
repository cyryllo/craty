<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            // Numer ewidencyjny, np. NAR-M1R3-2026-00042 — patrz App\Services\InventoryNumberGenerator.
            $table->string('inventory_no')->unique();
            $table->string('name');
            $table->text('description')->nullable();      // opis ogólny
            $table->text('specification')->nullable();     // specyfikacja techniczna
            $table->decimal('value', 10, 2)->nullable();    // wartość szacunkowa/zakupu
            $table->date('purchased_at')->nullable();
            $table->enum('condition', ['nowy', 'uzywany', 'uszkodzony'])->default('uzywany');
            $table->enum('status', [
                'dostepny', 'wypozyczony', 'w_naprawie', 'do_sprzedazy', 'sprzedany', 'wycofany',
            ])->default('dostepny');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('storage_location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('qr_path')->nullable(); // wygenerowany plik SVG z kodem QR
            $table->timestamps();

            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
