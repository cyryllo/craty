<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/app-storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/vendor/autoload.php';

// Świeży .env (skopiowany z .env.example, np. przy rozpakowaniu paczki
// instalacyjnej) nie ma jeszcze APP_KEY — a bez niego wysypuje się KAŻDE
// żądanie, jeszcze zanim appka zdąży pokazać web-owy Instalator (patrz
// routes/install.php), bo EncryptCookies w środkowej warstwie 'web'
// wymaga klucza już przy konstrukcji middleware'u. Dlatego generujemy go
// tutaj, zanim Laravel w ogóle wystartuje — nie w samym kreatorze.
$envWriter = new App\Support\EnvFileWriter(__DIR__.'/.env');
$envWriter->ensureExists(__DIR__.'/.env.example');
if ($envWriter->isWritable() && ! $envWriter->get('APP_KEY')) {
    $envWriter->set(['APP_KEY' => 'base64:'.base64_encode(random_bytes(32))]);
}

// Patrz App\Support\RequiredStorageDirectories — bez tego na świeżo
// rozpakowanej paczce wysypuje się KAŻDE żądanie (sesje/cache/widoki), jeszcze
// zanim Instalator zdąży cokolwiek pokazać. Tanie i idempotentne, więc zostaje
// tutaj, przed startem Laravela.
App\Support\RequiredStorageDirectories::ensureExist(__DIR__.'/app-storage');

$app = require_once __DIR__.'/bootstrap/app.php';

// Zwykle stawia to `artisan storage:link` (patrz InstallController), ale na
// już zainstalowanej appce, do której wgrano nowy kod bez ponownego
// przechodzenia przez instalator, nic więcej by tego nie zrobiło — tanie,
// więc sprawdzane na każde żądanie zamiast tylko przy instalacji. Bez tego
// symlinku zdjęcia przedmiotów i kody QR (dysk "public") 404-owałyby mimo
// poprawnych linków w HTML-u.
if (! is_link(__DIR__.'/storage') && ! is_dir(__DIR__.'/storage') && is_dir(__DIR__.'/app-storage/app/public')) {
    @symlink(__DIR__.'/app-storage/app/public', __DIR__.'/storage');
}

// Bootstrap Laravel and handle the request...
$app->handleRequest(Request::capture());
