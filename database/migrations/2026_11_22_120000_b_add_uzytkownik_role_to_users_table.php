<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Trzecia rola "uzytkownik" (np. członek makerspace'u): widzi tylko
 * rezerwacje maszyn i swój profil. Wzorzec zmiany enuma jak w
 * remove_podglad_role_from_users_table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['admin', 'magazynier', 'uzytkownik'])->default('magazynier')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'magazynier', 'uzytkownik') NOT NULL DEFAULT 'magazynier'");
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'uzytkownik')->update(['active' => false, 'role' => 'magazynier']);

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['admin', 'magazynier'])->default('magazynier')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'magazynier') NOT NULL DEFAULT 'magazynier'");
    }
};
