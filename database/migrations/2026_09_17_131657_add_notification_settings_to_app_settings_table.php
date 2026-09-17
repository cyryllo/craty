<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            // Domyślnie WŁĄCZONY — to już istniejąca, działająca funkcja
            // (Breeze'owy "Nie pamiętasz hasła?"), więc wyłączenie musi być
            // świadomą decyzją admina, nie domyślnym stanem po aktualizacji.
            $table->boolean('password_reset_enabled')->default(true);
            // Domyślnie WYŁĄCZONY — zupełnie nowa funkcja, nikt jej dziś nie
            // używa/oczekuje, więc nie ma ryzyka "zaskoczenia" przy aktualizacji.
            $table->boolean('loan_due_notifications_enabled')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn(['password_reset_enabled', 'loan_due_notifications_enabled']);
        });
    }
};
