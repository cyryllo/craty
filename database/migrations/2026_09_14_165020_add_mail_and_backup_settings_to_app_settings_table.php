<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            // SMTP — puste pola = appka i tak działa na tym, co jest w .env
            // (patrz App\Services\MailSettingsApplier).
            $table->string('mail_host')->nullable();
            $table->unsignedSmallInteger('mail_port')->nullable();
            $table->string('mail_encryption')->nullable(); // 'tls' | 'ssl' | null
            $table->string('mail_username')->nullable();
            $table->text('mail_password')->nullable(); // szyfrowane (Eloquent 'encrypted')
            $table->string('mail_from_address')->nullable();
            $table->string('mail_from_name')->nullable();

            // Backup — retencja w dniach i czy dołączać .env do kopii.
            $table->unsignedSmallInteger('backup_retention_days')->default(14);
            $table->boolean('backup_include_env')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn([
                'mail_host', 'mail_port', 'mail_encryption', 'mail_username',
                'mail_password', 'mail_from_address', 'mail_from_name',
                'backup_retention_days', 'backup_include_env',
            ]);
        });
    }
};
