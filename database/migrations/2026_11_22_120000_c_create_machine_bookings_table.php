<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moduł "Rezerwacje maszyn" (domyślnie wyłączony): rezerwacje czasu na
 * przedmiotach stacjonarnych. user_name to nazwa w chwili rezerwacji, jak
 * loans.borrower_name, żeby usunięcie konta nie zostawiało pustych wpisów.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('purpose', 20);
            $table->string('description', 1000)->nullable();
            $table->timestamps();

            $table->index(['item_id', 'starts_at']);
        });

        Schema::table('app_settings', function (Blueprint $table) {
            $table->boolean('module_bookings_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_bookings');
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn('module_bookings_enabled');
        });
    }
};
