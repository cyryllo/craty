<?php

namespace App\Services;

use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use ZipArchive;

/**
 * Zipuje katalog appki do jednego pliku — używane w dwóch miejscach z lekko
 * inną listą wykluczeń: `release:build` (paczka aktualizacji do dystrybucji)
 * i `UpdateService` (własna migawka kodu tuż przed zastosowaniem aktualizacji,
 * do ewentualnego rollbacku). Świadomie przyjmuje katalog źródłowy jako
 * parametr zamiast na sztywno `base_path()` — testy Modułu Aktualizacje
 * operują na kopii appki w katalogu tymczasowym, nigdy na tym repo.
 */
class UpdatePackageBuilder
{
    /** @param  array<int, string>  $excludes  Ścieżki względem $sourceDir (bez wiodącego "/"), np. "storage/logs". */
    public function build(string $sourceDir, string $outputZipPath, array $excludes = []): void
    {
        $sourceDir = rtrim($sourceDir, '/');

        if (! is_dir(dirname($outputZipPath))) {
            mkdir(dirname($outputZipPath), 0755, true);
        }

        $zip = new ZipArchive();
        $zip->open($outputZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $filter = new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
            fn (SplFileInfo $current) => ! $this->isExcluded($this->relativePath($sourceDir, $current), $excludes)
        );

        $iterator = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            $relative = $this->relativePath($sourceDir, $file);

            if ($file->isDir()) {
                $zip->addEmptyDir($relative);
            } else {
                $zip->addFile($file->getPathname(), $relative);
            }
        }

        $zip->close();
    }

    private function relativePath(string $sourceDir, SplFileInfo $file): string
    {
        return ltrim(substr($file->getPathname(), strlen($sourceDir)), '/');
    }

    /** @param  array<int, string>  $excludes */
    private function isExcluded(string $relativePath, array $excludes): bool
    {
        foreach ($excludes as $pattern) {
            $pattern = trim($pattern, '/');

            if ($relativePath === $pattern || str_starts_with($relativePath, $pattern.'/')) {
                return true;
            }
        }

        return false;
    }
}
