<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Dane startowe dla środowiska deweloperskiego: po jednym koncie na każdą
 * rolę + przykładowy spis (patrz DemoDataSeeder — to jedyna część, którą
 * wywołuje też Instalator w prawdziwym wdrożeniu, bez fałszywego konta
 * magazyniera).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@craty.test',
            'role' => 'admin',
        ]);
        // forceFill, bo 'protected' celowo nie jest w $fillable — to jedyne
        // miejsce w kodzie, które powinno je ustawiać.
        $admin->forceFill(['protected' => true])->save();

        User::factory()->create([
            'name' => 'Magazynier',
            'email' => 'magazynier@craty.test',
            'role' => 'magazynier',
        ]);

        $this->call(DemoDataSeeder::class);
    }
}
