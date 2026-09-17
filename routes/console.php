<?php

use App\Console\Commands\SendLoanDueNotifications;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sama komenda sprawdza włącznik (Ustawienia → Powiadomienia) i czy w ogóle
// jest co wysłać — nieszkodliwe, gdy uruchomione bez skonfigurowanego crona
// (po prostu nigdy się nie odpali). Wymaga `php artisan schedule:run` co
// minutę na hostingu — appka sama sobie crona nie założy.
Schedule::command(SendLoanDueNotifications::class)->dailyAt('08:00');
