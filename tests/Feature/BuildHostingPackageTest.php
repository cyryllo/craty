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
    }
}
