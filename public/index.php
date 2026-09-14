<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Świeży .env (skopiowany z .env.example, np. przy rozpakowaniu paczki
// instalacyjnej) nie ma jeszcze APP_KEY — a bez niego wysypuje się KAŻDE
// żądanie, jeszcze zanim appka zdąży pokazać web-owy Instalator (patrz
// routes/install.php), bo EncryptCookies w środkowej warstwie 'web'
// wymaga klucza już przy konstrukcji middleware'u. Dlatego generujemy go
// tutaj, zanim Laravel w ogóle wystartuje — nie w samym kreatorze.
$envWriter = new App\Support\EnvFileWriter(__DIR__.'/../.env');
$envWriter->ensureExists(__DIR__.'/../.env.example');
if ($envWriter->isWritable() && ! $envWriter->get('APP_KEY')) {
    $envWriter->set(['APP_KEY' => 'base64:'.base64_encode(random_bytes(32))]);
}

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
