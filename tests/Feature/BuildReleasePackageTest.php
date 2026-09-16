<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use ZipArchive;

/**
 * Ta komenda pakuje `base_path()` (prawdziwe repo) — bezpieczne, bo tylko
 * CZYTA drzewo źródłowe (UpdatePackageBuilder nie modyfikuje source dir).
 * `app.version_file_path` jest mimo to podmieniony na plik tymczasowy, żeby
 * `--version` nigdy nie nadpisał prawdziwego pliku VERSION tego repo.
 *
 * Appka ma dziś jeden, stały, spłaszczony układ (patrz CLAUDE.md "Project
 * layout"), więc ta jedna komenda/paczka służy zarówno do wgrania przez
 * panel jako aktualizacja, jak i do ręcznego rozpakowania na świeżej
 * instalacji — wcześniej istniała osobna komenda `release:build-hosting`
 * robiąca spłaszczenie w locie (`BuildHostingPackageTest`), zbędna teraz,
 * gdy repo samo jest już spłaszczone. Jej testy (`.htaccess`, brak
 * `public/`, dwa testy end-to-end: boot przez `php -S` i pełna instalacja z
 * serwowaniem zdjęcia przez symlink na prawdziwej MariaDB) zostały tu
 * przeniesione.
 */
class BuildReleasePackageTest extends TestCase
{
    use RefreshDatabase;

    private string $versionFile;

    private string $outputZip;

    protected function setUp(): void
    {
        parent::setUp();

        $this->versionFile = tempnam(sys_get_temp_dir(), 'craty-version-');
        file_put_contents($this->versionFile, "1.0.0\n");
        config(['app.version_file_path' => $this->versionFile]);

        $this->outputZip = sys_get_temp_dir().'/craty-release-test-'.uniqid().'.zip';
    }

    protected function tearDown(): void
    {
        @unlink($this->versionFile);
        @unlink($this->outputZip);
        @unlink($this->outputZip.'.sha256');
        parent::tearDown();
    }

    public function test_it_builds_a_package_with_a_valid_manifest(): void
    {
        $this->artisan('release:build', [
            'version' => '2.5.0',
            '--min-version' => '2.0.0',
            '--changelog' => ['Coś naprawione', 'Coś dodane'],
            '--output' => $this->outputZip,
        ])->assertSuccessful();

        $this->assertFileExists($this->outputZip);
        $this->assertSame("2.5.0\n", file_get_contents($this->versionFile));

        // Sidecar do weryfikacji pobranej paczki bez przepisywania hasha z konsoli.
        $this->assertFileExists($this->outputZip.'.sha256');
        $this->assertSame(
            hash_file('sha256', $this->outputZip).'  '.basename($this->outputZip)."\n",
            file_get_contents($this->outputZip.'.sha256')
        );

        $zip = new ZipArchive();
        $zip->open($this->outputZip);
        $manifest = json_decode($zip->getFromName('update-manifest.json'), true);

        $this->assertSame('2.5.0', $manifest['version']);
        $this->assertSame('2.0.0', $manifest['min_version']);
        $this->assertSame(['Coś naprawione', 'Coś dodane'], $manifest['changelog']);
        $this->assertNotEmpty($manifest['migrations']);

        // vendor/ i skompilowane assety muszą być w środku — cel to appka
        // bez composera/npm na docelowym hostingu. Appka nie ma osobnego
        // public/ — index.php, manifest.json (PWA) i inne statyczne pliki
        // leżą wprost w korzeniu.
        $this->assertNotFalse($zip->locateName('vendor/autoload.php'));
        $this->assertNotFalse($zip->locateName('artisan'));
        $this->assertNotFalse($zip->locateName('index.php'));
        $this->assertNotFalse($zip->locateName('manifest.json'));
        $this->assertFalse($zip->locateName('public/'), 'Appka nie ma już osobnego katalogu public/.');

        // Regresja: composer.json wyglądał na czysto deweloperski plik i
        // trafił na listę wykluczeń przy "sprzątaniu" paczki — ale Laravel
        // czyta go w RUNTIME (Application::getNamespace()), więc bez niego
        // appka wywalała się od razu przy starcie. composer.lock nie ma tego
        // problemu i zostaje wykluczony.
        $this->assertNotFalse($zip->locateName('composer.json'));
        $this->assertFalse($zip->locateName('composer.lock'));

        // Sekrety/deweloperskie pliki nie mają prawa się tam znaleźć.
        $this->assertFalse($zip->locateName('.env'));
        $this->assertFalse($zip->locateName('.git'));

        // Korzeń ma .htaccess blokujący .env/composer.json i kierujący ruch do index.php.
        $rootHtaccess = $zip->getFromName('.htaccess');
        $this->assertNotFalse($rootHtaccess);
        $this->assertStringContainsString('RewriteEngine On', $rootHtaccess);
        $this->assertStringContainsString('\.env', $rootHtaccess);

        // Każdy katalog z kodem/danymi ma własny .htaccess blokujący dostęp z przeglądarki.
        // "app-storage" (prawdziwy storage/ appki) CELOWO nie ma własnego
        // zagnieżdżonego .htaccess — patrz niżej.
        foreach (['app', 'bootstrap', 'config', 'database', 'lang', 'resources', 'routes', 'vendor'] as $dir) {
            $htaccess = $zip->getFromName($dir.'/.htaccess');
            $this->assertNotFalse($htaccess, "Brak {$dir}/.htaccess w paczce.");
            $this->assertStringContainsString('Require all denied', $htaccess);
        }

        // Regresja: app-storage/.htaccess z blanket "Require all denied"
        // blokował też app-storage/app/public, do którego prowadzi symlink
        // "storage" (Apache stosuje reguły dostępu po prawdziwej ścieżce
        // docelowej symlinka) — zdjęcia/QR dawały 403 mimo poprawnego
        // symlinku. app-storage jest blokowany PO ADRESIE URL w korzeniu,
        // nie zagnieżdżonym plikiem w środku katalogu.
        $this->assertFalse($zip->locateName('app-storage/.htaccess'), 'app-storage nie może mieć własnego .htaccess — blokuje wtedy też pliki dostępne przez symlink storage.');
        $this->assertStringContainsString('^/app-storage/', $rootHtaccess);

        // Symlink "storage" sam nie jest pakowany (wskazuje na te same
        // zdjęcia co app-storage/app/public, już wykluczone) — inaczej
        // dublowałby te same pliki pod dwiema nazwami w zipie.
        $this->assertFalse($zip->locateName('storage/'));

        // index.php nie zakłada już katalogu wyżej — appka i document root
        // to to samo miejsce.
        $indexPhp = $zip->getFromName('index.php');
        $this->assertStringNotContainsString("__DIR__.'/../", $indexPhp);
        $this->assertStringContainsString("__DIR__.'/vendor/autoload.php'", $indexPhp);
        // Samo-naprawiający się symlink — na już zainstalowanej appce nic
        // innego by go nie stworzyło, bo Instalator już się nie pokaże.
        $this->assertStringContainsString('symlink(__DIR__', $indexPhp);

        // usePublicPath()/useStoragePath() siedzą w bootstrap/app.php (nie w
        // index.php) — dotyczą KAŻDEGO punktu wejścia, nie tylko HTTP.
        // Bez usePublicPath(__DIR__) Vite szuka manifestu pod podwójnym
        // "public/build/..." i appka wywala ViteManifestNotFoundException.
        // Bez useStoragePath(__DIR__.'/app-storage') storage_path() liczyłby
        // się jako __DIR__.'/storage' — dokładnie tam, gdzie storage:link
        // chce postawić symlink — symlink() nie może podmienić istniejącego
        // katalogu, więc storage:link cicho padał i zdjęcia/QR nigdy nie
        // miały czego serwować pod /storage/... (realnie złapane na
        // produkcji, patrz testy end-to-end niżej).
        $bootstrapApp = $zip->getFromName('bootstrap/app.php');
        $this->assertNotFalse($bootstrapApp);
        $this->assertStringContainsString('usePublicPath($app->basePath())', $bootstrapApp);
        $this->assertStringContainsString("useStoragePath(\$app->basePath('app-storage'))", $bootstrapApp);

        // Fixtures ze Storage::fake() z uruchomień testów na maszynie
        // budującej — czysty śmieć, wielokrotnie realnie łapany w paczce.
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $this->assertStringStartsNotWith('app-storage/framework/testing/', $zip->getNameIndex($i));
        }

        // Regresja: builder pakował sam siebie — poprzednie .zip-y z
        // app-storage/app/releases (wyjście tej samej komendy) trafiały do
        // środka nowej paczki, więc każde kolejne wydanie puchło o rozmiar
        // wszystkich poprzednich (realnie znalezione: 1.1.0 spuchło do 49 MB,
        // bo wciągnęło całą paczkę 1.0.1).
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $this->assertStringStartsNotWith('app-storage/app/releases/', $zip->getNameIndex($i));
        }

        // Regresja: ten Dockerfile/dev kontener stoi na PHP 8.4, więc
        // `composer update` odpalony na tej maszynie bez pilnowania
        // config.platform.php w composer.json cicho dociąga wersje pakietów
        // (endroid/qr-code, symfony/*), które wymagają PHP 8.4 — appka
        // deklaruje wsparcie od 8.3. Realnie złapane: produkcja na PHP
        // 8.3.26 wywalała się na starcie (platform_check.php).
        $platformCheck = $zip->getFromName('vendor/composer/platform_check.php');
        $this->assertNotFalse($platformCheck);
        $this->assertStringContainsString('PHP_VERSION_ID >= 80300', $platformCheck);
        $this->assertStringNotContainsString('80400', $platformCheck);
    }

    /**
     * Nie tylko sprawdzamy zawartość plików — realnie ROZPAKOWUJEMY paczkę i
     * odpytujemy ją prawdziwym żądaniem HTTP przez `php -S` wprost na
     * spłaszczonym katalogu (tak jak Apache, tylko bez samego Apache —
     * `php -S` bez routera już samo z siebie odpala index.php w korzeniu dla
     * nieistniejących ścieżek, dokładnie jak `DirectoryIndex`). To właśnie
     * ten test złapałby każdy z błędów znalezionych ręcznie na produkcji
     * przy tej paczce: brakujący composer.json, brakujące
     * storage/framework/*, zły publicPath() dla Vite — każdy z nich kończył
     * się realnie inną, ale zawsze NIE-200 odpowiedzią na GET /install.
     */
    public function test_the_built_package_actually_boots_and_serves_the_installer(): void
    {
        $extractDir = sys_get_temp_dir().'/craty-release-extract-'.uniqid();

        $this->artisan('release:build', [
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
     * Pełny cykl: prawdziwa instalacja (z prawdziwą bazą MariaDB — tą samą,
     * na której stoi ten kontener, przez tymczasową bazę scratch, jak w
     * InstallerTest) z zaznaczonymi danymi przykładowymi, a potem realne
     * pobranie wygenerowanego kodu QR spod /storage/... — to właśnie ten
     * request wywalał się realnie na produkcji (symlink() nie mógł
     * podmienić prawdziwego katalogu storage/ appki na link).
     */
    public function test_the_built_package_actually_serves_photos_after_a_real_install(): void
    {
        $database = 'craty_release_storage_test';
        $this->createScratchDatabase($database);
        $extractDir = sys_get_temp_dir().'/craty-release-extract-'.uniqid();

        $this->artisan('release:build', [
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
            $cookieJar = tempnam(sys_get_temp_dir(), 'craty-release-cookies-');

            $install = $this->curl($base.'/install', $cookieJar);
            preg_match('/name="csrf-token" content="([^"]+)"/', $install['body'], $m);
            $token = $m[1] ?? $this->fail('Brak csrf-token na /install.');

            $store = $this->curl($base.'/install', $cookieJar, [
                'db_host' => 'db', 'db_port' => '3306', 'db_database' => $database,
                'db_username' => 'root', 'db_password' => 'root',
                'app_name' => 'Release Storage Test', 'app_url' => $base,
                'admin_name' => 'Admin', 'admin_email' => 'admin@example.com',
                'admin_password' => 'Passw0rd!123', 'admin_password_confirmation' => 'Passw0rd!123',
                'demo_data' => '1',
            ], $token);
            $this->assertSame(302, $store['status'], "POST /install: {$store['status']}\n".$store['body']);

            $qrPath = DB::connection('mysql_release_test')->table('items')->value('qr_path');
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
        config(['database.connections.mysql_release_test' => [
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
        DB::purge('mysql_release_test');
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
