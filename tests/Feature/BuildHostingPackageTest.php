<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use ZipArchive;

/**
 * Podobnie jak BuildReleasePackageTest — pakuje prawdziwe `base_path()`
 * (tylko do odczytu), więc bezpieczne do uruchomienia na tym repo. Sprawdza
 * spłaszczoną strukturę pod hosting z open_basedir ograniczonym do samego
 * document rootu (patrz komentarz w BuildHostingPackage).
 */
class BuildHostingPackageTest extends TestCase
{
    use RefreshDatabase;

    private string $outputZip;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outputZip = sys_get_temp_dir().'/craty-hosting-test-'.uniqid().'.zip';
    }

    protected function tearDown(): void
    {
        @unlink($this->outputZip);
        @unlink($this->outputZip.'.sha256');
        parent::tearDown();
    }

    public function test_it_flattens_public_and_protects_sensitive_directories(): void
    {
        $this->artisan('release:build-hosting', [
            'version' => '2.5.0',
            '--output' => $this->outputZip,
        ])->assertSuccessful();

        $this->assertFileExists($this->outputZip);

        // Sidecar do weryfikacji pobranej paczki bez przepisywania hasha z konsoli.
        $this->assertFileExists($this->outputZip.'.sha256');
        $this->assertSame(
            hash_file('sha256', $this->outputZip).'  '.basename($this->outputZip)."\n",
            file_get_contents($this->outputZip.'.sha256')
        );

        $zip = new ZipArchive();
        $zip->open($this->outputZip);

        // public/ znika jako katalog — jego zawartość ląduje w korzeniu.
        $this->assertFalse($zip->locateName('public/'));
        $this->assertNotFalse($zip->locateName('index.php'));
        $this->assertNotFalse($zip->locateName('manifest.json'));
        $this->assertNotFalse($zip->locateName('vendor/autoload.php'));

        // index.php nie może już zakładać, że appka jest katalog wyżej.
        $indexPhp = $zip->getFromName('index.php');
        $this->assertStringNotContainsString("__DIR__.'/../", $indexPhp);
        $this->assertStringContainsString("__DIR__.'/vendor/autoload.php'", $indexPhp);

        // Korzeń ma .htaccess blokujący .env/composer.json i kierujący ruch do index.php.
        $rootHtaccess = $zip->getFromName('.htaccess');
        $this->assertNotFalse($rootHtaccess);
        $this->assertStringContainsString('RewriteEngine On', $rootHtaccess);
        $this->assertStringContainsString('\.env', $rootHtaccess);

        // Każdy katalog z kodem/danymi ma własny .htaccess blokujący dostęp z przeglądarki.
        // "storage" NIE jest na tej liście — to teraz "app-storage" (patrz
        // renameStorageDirectory()), bo prawdziwy "storage" pod document
        // rootem musi zostać wolny pod symlink z artisan storage:link.
        foreach (['app', 'bootstrap', 'config', 'database', 'lang', 'resources', 'routes', 'app-storage', 'vendor'] as $dir) {
            $htaccess = $zip->getFromName($dir.'/.htaccess');
            $this->assertNotFalse($htaccess, "Brak {$dir}/.htaccess w paczce.");
            $this->assertStringContainsString('Require all denied', $htaccess);
        }
        $this->assertFalse($zip->locateName('storage/'), 'storage/ powinno zniknąć jako katalog (zmienione na app-storage/, wolne pod symlink).');

        // $app->usePublicPath(__DIR__) — bez tego Vite szuka manifestu pod
        // podwójnym "public/build/..." (publicPath() domyślnie = base_path().
        // '/public', a po spłaszczeniu public/ w ogóle nie istnieje) i appka
        // wywala ViteManifestNotFoundException na pierwszym GET /install.
        $this->assertStringContainsString('usePublicPath(__DIR__)', $indexPhp);

        // $app->useStoragePath(__DIR__.'/app-storage') — bez tego
        // storage_path() nadal liczy się jako __DIR__.'/storage', czyli
        // dokładnie tam, gdzie artisan storage:link chce postawić symlink
        // (public_path('storage') == __DIR__.'/storage', bo publicPath ==
        // basePath po spłaszczeniu) — symlink() nie ma prawa podmienić
        // istniejącego katalogu, więc storage:link cicho padał i zdjęcia/QR
        // nigdy nie miały czego serwować pod /storage/... (realnie złapane
        // na produkcji, patrz pełny test end-to-end niżej).
        $this->assertStringContainsString("useStoragePath(__DIR__.'/app-storage')", $indexPhp);

        // Samo-naprawiający się symlink — na już zainstalowanej appce (starsza
        // wersja tej paczki, sprzed tej poprawki) nic innego by go nie stworzyło,
        // bo Instalator już się nie pokaże po pierwszej instalacji.
        $this->assertStringContainsString('symlink(__DIR__', $indexPhp);
    }

    /**
     * Nie tylko sprawdzamy zawartość plików — realnie ROZPAKOWUJEMY paczkę i
     * odpytujemy ją prawdziwym żądaniem HTTP przez php -S wprost na
     * spłaszczonym katalogu (tak jak Apache na hostingu, NIE przez
     * `php artisan serve` — ten na sztywno próbuje `chdir()` do public/,
     * którego w spłaszczonej paczce już nie ma). To właśnie ten test złapałby
     * każdy z błędów znalezionych ręcznie na produkcji przy tej paczce:
     * brakujący composer.json, brakujące storage/framework/*, zły publicPath()
     * dla Vite — każdy z nich kończył się realnie inną, ale zawsze NIE-200
     * odpowiedzią na GET /install.
     */
    public function test_the_built_package_actually_boots_and_serves_the_installer(): void
    {
        $extractDir = sys_get_temp_dir().'/craty-hosting-extract-'.uniqid();

        $this->artisan('release:build-hosting', [
            'version' => '2.5.0',
            '--output' => $this->outputZip,
        ])->assertSuccessful();

        $zip = new ZipArchive();
        $zip->open($this->outputZip);
        $zip->extractTo($extractDir);
        $zip->close();

        [$process, $port] = $this->startFlattenedServer($extractDir);

        try {
            $this->waitUntilListening('127.0.0.1', $port);

            $response = @file_get_contents("http://127.0.0.1:{$port}/install");
            $status = $http_response_header[0] ?? '(brak odpowiedzi — serwer nie wystartował?)';

            $this->assertStringContainsString(' 200 ', $status, "GET /install na spłaszczonej paczce: {$status}\n".($response ?: '(pusta odpowiedź)'));
            $this->assertStringContainsString('Installation', (string) $response);
        } finally {
            if (is_resource($process)) {
                proc_terminate($process);
                proc_close($process);
            }
            (new \Illuminate\Filesystem\Filesystem)->deleteDirectory($extractDir);
        }
    }

    /**
     * Pełny cykl na spłaszczonej paczce: prawdziwa instalacja (z prawdziwą
     * bazą MariaDB — tą samą, na której stoi ten kontener, przez tymczasową
     * bazę scratch, jak w InstallerTest) z zaznaczonymi danymi przykładowymi,
     * a potem realne pobranie wygenerowanego kodu QR spod /storage/... —
     * to właśnie ten request wywalał się realnie na produkcji (symlink()
     * nie mógł podmienić prawdziwego katalogu storage/ appki na link).
     */
    public function test_the_built_package_actually_serves_photos_after_a_real_install(): void
    {
        $database = 'craty_hosting_storage_test';
        $this->createScratchDatabase($database);
        $extractDir = sys_get_temp_dir().'/craty-hosting-extract-'.uniqid();

        $this->artisan('release:build-hosting', [
            'version' => '2.5.0',
            '--output' => $this->outputZip,
        ])->assertSuccessful();

        $zip = new ZipArchive();
        $zip->open($this->outputZip);
        $zip->extractTo($extractDir);
        $zip->close();

        [$process, $port] = $this->startFlattenedServer($extractDir);

        try {
            $this->waitUntilListening('127.0.0.1', $port);
            $base = "http://127.0.0.1:{$port}";
            $cookieJar = tempnam(sys_get_temp_dir(), 'craty-hosting-cookies-');

            $install = $this->curl($base.'/install', $cookieJar);
            preg_match('/name="csrf-token" content="([^"]+)"/', $install['body'], $m);
            $token = $m[1] ?? $this->fail('Brak csrf-token na /install.');

            $store = $this->curl($base.'/install', $cookieJar, [
                'db_host' => 'db', 'db_port' => '3306', 'db_database' => $database,
                'db_username' => 'root', 'db_password' => 'root',
                'app_name' => 'Hosting Storage Test', 'app_url' => $base,
                'admin_name' => 'Admin', 'admin_email' => 'admin@example.com',
                'admin_password' => 'Passw0rd!123', 'admin_password_confirmation' => 'Passw0rd!123',
                'demo_data' => '1',
            ], $token);
            $this->assertSame(302, $store['status'], "POST /install: {$store['status']}\n".$store['body']);

            $qrPath = DB::connection('mysql_hosting_test')->table('items')->value('qr_path');
            $this->assertNotNull($qrPath, 'Instalacja z danymi przykładowymi powinna dać przynajmniej jeden przedmiot z kodem QR.');

            $photo = $this->curl($base.'/storage/'.$qrPath, $cookieJar);
            $this->assertSame(200, $photo['status'], "GET /storage/{$qrPath} po instalacji: {$photo['status']}\n".$photo['body']);
        } finally {
            if (is_resource($process)) {
                proc_terminate($process);
                proc_close($process);
            }
            (new \Illuminate\Filesystem\Filesystem)->deleteDirectory($extractDir);
            $this->dropScratchDatabase($database);
            if (isset($cookieJar) && file_exists($cookieJar)) {
                unlink($cookieJar);
            }
        }
    }

    /** @return array{status: int, body: string} */
    private function curl(string $url, string $cookieJar, ?array $post = null, ?string $csrfToken = null): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $cookieJar,
            CURLOPT_COOKIEFILE => $cookieJar,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        if ($post !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-CSRF-TOKEN: {$csrfToken}"]);
        }
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status' => $status, 'body' => (string) $body];
    }

    private function createScratchDatabase(string $name): void
    {
        $pdo = new \PDO('mysql:host=db;port=3306', 'root', 'root');
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}`");

        // Jawnie host/port zamiast dziedziczyć z config('database.connections.mysql')
        // — środowisko testowe (sqlite) niekoniecznie ma tam poprawnie
        // ustawiony host "db" (nieużywany na co dzień przy DB_CONNECTION=sqlite).
        config(['database.connections.mysql_hosting_test' => [
            'driver' => 'mysql', 'host' => 'db', 'port' => 3306,
            'database' => $name, 'username' => 'root', 'password' => 'root',
            'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci',
        ]]);
    }

    private function dropScratchDatabase(string $name): void
    {
        try {
            $pdo = new \PDO('mysql:host=db;port=3306', 'root', 'root');
            $pdo->exec("DROP DATABASE IF EXISTS `{$name}`");
        } catch (\Throwable) {
            // Sprzątanie best-effort.
        }
        DB::purge('mysql_hosting_test');
    }

    /**
     * `proc_open()` domyślnie dziedziczy CAŁE środowisko procesu PHPUnit —
     * a to jest odpalane przez `./test` z APP_ENV=testing, więc .env.testing
     * (SESSION_DRIVER=array, DB_CONNECTION=sqlite...) trafia do procesu przez
     * putenv() i realnie ISTNIEJE w środowisku systemowym tego procesu.
     * Zwykły child proces (jak spawnowany tu `php -S`) dziedziczy to po
     * rodzicu, a phpdotenv (immutable) NIE nadpisze już ustawionych zmiennych
     * własnym plikiem .env spłaszczonej paczki — appka pod testowanym
     * serwerem cicho używała sesji w pamięci (array) zamiast plikowej, więc
     * token CSRF z GET nigdy nie przeżywał do POST (419 zamiast 302). Dajemy
     * więc `php -S` jawne, czyste środowisko zamiast dziedziczonego.
     *
     * @return array{0: resource, 1: int}
     */
    private function startFlattenedServer(string $extractDir): array
    {
        $port = $this->findFreePort();

        $process = proc_open(
            ['php', '-S', "127.0.0.1:{$port}"],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
            $extractDir,
            ['PATH' => getenv('PATH'), 'HOME' => sys_get_temp_dir()]
        );

        return [$process, $port];
    }

    private function findFreePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr($name, strrpos($name, ':') + 1);
    }

    private function waitUntilListening(string $host, int $port): void
    {
        $deadline = microtime(true) + 5;

        do {
            $connection = @fsockopen($host, $port, $errno, $errstr, 0.1);
            if ($connection) {
                fclose($connection);

                return;
            }
            usleep(50000);
        } while (microtime(true) < $deadline);

        $this->fail("php -S na {$host}:{$port} nie wystartował w 5 sekund.");
    }
}
