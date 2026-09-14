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

        // Sekrety/deweloperskie pliki nie mają prawa się tam znaleźć.
        $this->assertFalse($zip->locateName('.env'));
        $this->assertFalse($zip->locateName('.git'));
    }
}
