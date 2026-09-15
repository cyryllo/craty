<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Usuwa rolę "podglad" (tylko odczyt) — na życzenie użytkownika, zostają
 * tylko admin/magazynier. Przed zmianą enuma przepisujemy ewentualnych
 * istniejących użytkowników z tą rolą na "magazynier", żeby ALTER nie
 * wywalił się na danych, które nie mieszczą się już w nowej liście wartości.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'podglad')->update(['role' => 'magazynier']);

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['admin', 'magazynier'])->default('magazynier')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'magazynier') NOT NULL DEFAULT 'magazynier'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['admin', 'magazynier', 'podglad'])->default('magazynier')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'magazynier', 'podglad') NOT NULL DEFAULT 'magazynier'");
    }
};
