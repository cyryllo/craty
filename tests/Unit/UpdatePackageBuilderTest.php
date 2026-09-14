<?php

namespace Tests\Unit;

use App\Services\UpdatePackageBuilder;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class UpdatePackageBuilderTest extends TestCase
{
    private string $sourceDir;

    private string $outputZip;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sourceDir = sys_get_temp_dir().'/craty-builder-test-'.uniqid();
        mkdir($this->sourceDir.'/app', 0755, true);
        mkdir($this->sourceDir.'/vendor/some-package', 0755, true);
        mkdir($this->sourceDir.'/node_modules/some-lib', 0755, true);
        mkdir($this->sourceDir.'/storage/logs', 0755, true);

        file_put_contents($this->sourceDir.'/app/Item.php', '<?php // model');
        file_put_contents($this->sourceDir.'/vendor/some-package/autoload.php', '<?php // vendor');
        file_put_contents($this->sourceDir.'/node_modules/some-lib/index.js', '// js');
        file_put_contents($this->sourceDir.'/storage/logs/laravel.log', 'log entry');
        file_put_contents($this->sourceDir.'/.env', 'APP_KEY=secret');

        $this->outputZip = sys_get_temp_dir().'/craty-builder-output-'.uniqid().'.zip';
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->sourceDir);
        @unlink($this->outputZip);
        parent::tearDown();
    }

    public function test_build_includes_app_and_vendor_but_excludes_dev_and_secret_files(): void
    {
        (new UpdatePackageBuilder())->build($this->sourceDir, $this->outputZip, [
            'node_modules', 'storage/logs', '.env',
        ]);

        $zip = new ZipArchive();
        $zip->open($this->outputZip);

        $this->assertNotFalse($zip->locateName('app/Item.php'));
        $this->assertNotFalse($zip->locateName('vendor/some-package/autoload.php'));
        $this->assertFalse($zip->locateName('node_modules/some-lib/index.js'));
        $this->assertFalse($zip->locateName('storage/logs/laravel.log'));
        $this->assertFalse($zip->locateName('.env'));
    }

    public function test_build_with_no_excludes_includes_everything(): void
    {
        (new UpdatePackageBuilder())->build($this->sourceDir, $this->outputZip);

        $zip = new ZipArchive();
        $zip->open($this->outputZip);

        $this->assertNotFalse($zip->locateName('.env'));
        $this->assertNotFalse($zip->locateName('node_modules/some-lib/index.js'));
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($path);
    }
}
