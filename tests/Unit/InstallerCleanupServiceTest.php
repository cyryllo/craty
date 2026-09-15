<?php

namespace Tests\Unit;

use App\Services\InstallerCleanupService;
use PHPUnit\Framework\TestCase;

class InstallerCleanupServiceTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/craty-cleanup-test-'.uniqid();
        mkdir($this->root.'/routes', 0755, true);
        mkdir($this->root.'/app/Http/Controllers', 0755, true);
        mkdir($this->root.'/resources/views/install', 0755, true);
        mkdir($this->root.'/resources/views/other', 0755, true);

        file_put_contents($this->root.'/routes/web.php', <<<'PHP'
            <?php
            Route::get('/dashboard', fn () => 'x');
            require __DIR__.'/auth.php';
            require __DIR__.'/install.php';

            PHP);
        file_put_contents($this->root.'/routes/install.php', '<?php // trasy kreatora');
        file_put_contents($this->root.'/app/Http/Controllers/InstallController.php', '<?php // kontroler kreatora');
        file_put_contents($this->root.'/resources/views/install/wizard.blade.php', '<div>kreator</div>');
        file_put_contents($this->root.'/resources/views/other/keep-me.blade.php', '<div>zostaje</div>');
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_it_removes_installer_files_and_the_require_line(): void
    {
        (new InstallerCleanupService($this->root))->removeInstallerFiles();

        $this->assertFileDoesNotExist($this->root.'/routes/install.php');
        $this->assertFileDoesNotExist($this->root.'/app/Http/Controllers/InstallController.php');
        $this->assertDirectoryDoesNotExist($this->root.'/resources/views/install');

        $webRoutes = file_get_contents($this->root.'/routes/web.php');
        $this->assertStringNotContainsString('install.php', $webRoutes);
        $this->assertStringContainsString("require __DIR__.'/auth.php';", $webRoutes);
        $this->assertStringContainsString("Route::get('/dashboard'", $webRoutes);
    }

    public function test_it_leaves_unrelated_views_alone(): void
    {
        (new InstallerCleanupService($this->root))->removeInstallerFiles();

        $this->assertFileExists($this->root.'/resources/views/other/keep-me.blade.php');
    }

    public function test_it_does_not_throw_when_files_are_already_gone(): void
    {
        $service = new InstallerCleanupService($this->root);
        $service->removeInstallerFiles();

        // Wołane drugi raz (np. podwójny klik) — nie ma prawa wybuchnąć.
        $service->removeInstallerFiles();

        $this->assertFileDoesNotExist($this->root.'/routes/install.php');
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
