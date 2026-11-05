<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moduł Kontakty: osoby, firmy i serwisy, którym sprzęt "wychodzi" z
 * magazynu. `type` to zwykły string walidowany w aplikacji (Contact::TYPES),
 * nie enum w bazie: dodanie typu nie wymaga wtedy ALTER TABLE na MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 20)->default('osoba');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
