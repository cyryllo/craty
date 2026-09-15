<?php

namespace App\Services;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Usuwa pliki kreatora instalacji z serwera po udanej instalacji — opcjonalne
 * domknięcie luki: sama blokada EnsureNotInstalled trzyma się bazy
 * (`User::query()->exists()`), więc chwilowa awaria połączenia z bazą
 * ponownie odsłoniłaby kreator, dopóki te pliki tam leżą. Root skonfigurowany
 * przez `app.update_root_path` (ten sam klucz co UpdateService — oba opisują
 * "katalog, w którym leży ta appka"), żeby dało się to bezpiecznie
 * przetestować na katalogu tymczasowym, nigdy na tym repo.
 */
class InstallerCleanupService
{
    public function __construct(private readonly string $root)
    {
    }

    public function removeInstallerFiles(): void
    {
        // Kolejność ma znaczenie: najpierw usuwamy odwołanie z routes/web.php,
        // *zanim* usuniemy sam plik tras — inaczej kolejne żądanie trafiłoby
        // na require nieistniejącego pliku i wywaliło całą appkę.
        $webRoutes = $this->root.'/routes/web.php';

        if (file_exists($webRoutes)) {
            file_put_contents(
                $webRoutes,
                str_replace("require __DIR__.'/install.php';\n", '', file_get_contents($webRoutes))
            );
        }

        $this->deleteFile($this->root.'/routes/install.php');
        $this->deleteFile($this->root.'/app/Http/Controllers/InstallController.php');
        $this->deleteDirectory($this->root.'/resources/views/install');
    }

    private function deleteFile(string $path): void
    {
        // file_exists(), nie tylko unlink() z @ — PHPUnit 11 zgłasza jako
        // "warning" nawet stłumiony błąd unlink() na nieistniejącym pliku
        // (patrz test "wołane drugi raz nie wybucha").
        if (is_file($path)) {
            unlink($path);
        }
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : $this->deleteFile($file->getPathname());
        }

        rmdir($path);
    }
}
