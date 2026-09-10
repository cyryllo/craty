<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pojedynczy wiersz (id=1) z ustawieniami wyglądu appki — nazwa i logo,
     * edytowalne przez admina w Ustawienia → Ustawienia aplikacji. Puste pola
     * oznaczają "użyj domyślnych" (config('app.name'), domyślne logo SVG).
     */
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
