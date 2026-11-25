<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dziennik aktywności: zawsze bezpieczeństwo i administracja (logowania,
 * konta, ustawienia, aktualizacje), a zmiany danych tylko po włączeniu
 * przez administratora (activity_log_all_enabled). Wpisy starsze niż rok
 * usuwają się same (ActivityLog::RETENTION_DAYS). user_name to nazwa w
 * chwili zdarzenia (przy nieudanym logowaniu: wpisany e-mail).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('event', 40);
            $table->string('category', 10);
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['category', 'created_at']);
        });

        Schema::table('app_settings', function (Blueprint $table) {
            $table->boolean('activity_log_all_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn('activity_log_all_enabled');
        });
    }
};
