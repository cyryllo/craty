<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // admin        - pełny dostęp, zarządza użytkownikami i słownikami
            // magazynier   - dodaje/edytuje przedmioty, wypożyczenia, eksport ofert
            // podglad      - tylko odczyt (np. dla klienta lub współpracownika)
            $table->enum('role', ['admin', 'magazynier', 'podglad'])
                ->default('magazynier')
                ->after('email');
            $table->boolean('active')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'active']);
        });
    }
};
