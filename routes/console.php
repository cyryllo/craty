<?php

use App\Services\BackupService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup automatyczny — wymaga, żeby na serwerze `php artisan schedule:run`
// leciał co minutę z cron-a (patrz Ustawienia → Kopie zapasowe). Wołamy
// BackupService, a nie `backup:run`/`backup:clean` bezpośrednio, żeby
// zaplanowane uruchomienia też respektowały ustawienia admina (retencja,
// dołączanie .env), a nie tylko domyślną konfigurację z config/backup.php.
Schedule::call(fn () => app(BackupService::class)->run())->daily();
Schedule::call(fn () => app(BackupService::class)->cleanup())->daily();
