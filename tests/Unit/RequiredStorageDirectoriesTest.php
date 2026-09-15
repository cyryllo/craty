<?php

namespace Tests\Unit;

use App\Support\RequiredStorageDirectories;
use PHPUnit\Framework\TestCase;

/**
 * Regresja: na świeżo rozpakowanej paczce .zip (release:build wyklucza
 * zawartość tych katalogów, patrz UpdatePaths::PACKAGE_EXCLUDES) katalogi
 * storage/framework/{sessions,views,cache/data} i storage/logs w ogóle nie
 * istniały — pierwsze żądanie wysypywało się na
 * "file_put_contents(...storage/framework/sessions/...): No such file or
 * directory", zanim Instalator zdążył cokolwiek pokazać. Złapane realnie przy
 * ręcznym rozpakowaniu i odpaleniu paczki "-full" od zera.
 */
class RequiredStorageDirectoriesTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storagePath = sys_get_temp_dir().'/craty-storage-dirs-test-'.uniqid();
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->storagePath);
        parent::tearDown();
    }

    public function test_it_creates_all_required_directories_from_scratch(): void
    {
        RequiredStorageDirectories::ensureExist($this->storagePath);

        foreach (RequiredStorageDirectories::DIRECTORIES as $dir) {
            $this->assertDirectoryExists($this->storagePath.'/'.$dir);
        }
    }

    public function test_it_does_not_throw_when_some_directories_already_exist(): void
    {
        mkdir($this->storagePath.'/framework/sessions', 0755, true);

        RequiredStorageDirectories::ensureExist($this->storagePath);

        $this->assertDirectoryExists($this->storagePath.'/framework/views');
        $this->assertDirectoryExists($this->storagePath.'/logs');
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
