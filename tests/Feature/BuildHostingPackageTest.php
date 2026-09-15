<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
        parent::tearDown();
    }

    public function test_it_flattens_public_and_protects_sensitive_directories(): void
    {
        $this->artisan('release:build-hosting', [
            'version' => '2.5.0',
            '--output' => $this->outputZip,
        ])->assertSuccessful();

        $this->assertFileExists($this->outputZip);

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
        foreach (['app', 'bootstrap', 'config', 'database', 'lang', 'resources', 'routes', 'storage', 'vendor'] as $dir) {
            $htaccess = $zip->getFromName($dir.'/.htaccess');
            $this->assertNotFalse($htaccess, "Brak {$dir}/.htaccess w paczce.");
            $this->assertStringContainsString('Require all denied', $htaccess);
        }

        // $app->usePublicPath(__DIR__) — bez tego Vite szuka manifestu pod
        // podwójnym "public/build/..." (publicPath() domyślnie = base_path().
        // '/public', a po spłaszczeniu public/ w ogóle nie istnieje) i appka
        // wywala ViteManifestNotFoundException na pierwszym GET /install.
        // Realnie złapane na produkcji, patrz test end-to-end niżej.
        $this->assertStringContainsString('usePublicPath(__DIR__)', $indexPhp);
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

        $port = $this->findFreePort();
        $process = proc_open(
            ['php', '-S', "127.0.0.1:{$port}"],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $extractDir
        );

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
