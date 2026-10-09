<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ustawienia sklepu: opis sklepu (w ramce kontaktu na stronie głównej) oraz
 * polityka prywatności i regulamin (linki w stopce, tekst w okienku). Prosty
 * Markdown, wyświetlany przez AppSetting::markdown() bez surowego HTML.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->text('shop_description')->nullable();
            $table->text('privacy_policy')->nullable();
            $table->text('terms')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn(['shop_description', 'privacy_policy', 'terms']);
        });
    }
};
