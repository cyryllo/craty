<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Przedmiot stacjonarny (maszyna na stałe w pracowni: drukarka 3D, ploter
 * laserowy): nie da się go wypożyczyć ani zarezerwować na dni, za to przy
 * module "Rezerwacje maszyn" rezerwuje się na nim czas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->boolean('stationary')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('stationary');
        });
    }
};
