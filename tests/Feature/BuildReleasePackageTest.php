<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

/**
 * Ta komenda pakuje `base_path()` (prawdziwe repo) — bezpieczne, bo tylko
 * CZYTA drzewo źródłowe (UpdatePackageBuilder nie modyfikuje source dir).
 * `app.version_file_path` jest mimo to podmieniony na plik tymczasowy, żeby
 * `--version` nigdy nie nadpisał prawdziwego pliku VERSION tego repo.
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
        // bez composera/npm na docelowym hostingu.
        $this->assertNotFalse($zip->locateName('vendor/autoload.php'));
        $this->assertNotFalse($zip->locateName('artisan'));

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

        // Fixtures ze Storage::fake() z uruchomień testów na maszynie
        // budującej — czysty śmieć, wielokrotnie realnie łapany w paczce.
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $this->assertStringStartsNotWith('storage/framework/testing/', $zip->getNameIndex($i));
        }

        // Regresja: builder pakował sam siebie — poprzednie .zip-y z
        // storage/app/releases (wyjście tej samej komendy) trafiały do środka
        // nowej paczki, więc każde kolejne wydanie puchło o rozmiar wszystkich
        // poprzednich (realnie znalezione: 1.1.0 spuchło do 49 MB, bo
        // wciągnęło całą paczkę 1.0.1).
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $this->assertStringStartsNotWith('storage/app/releases/', $zip->getNameIndex($i));
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
}
