<?php

namespace App\Services;

use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use ZipArchive;

/**
 * Zipuje katalog appki do jednego pliku — używane przez `release:build`/
 * `release:build-hosting` do zbudowania paczki aktualizacji do dystrybucji.
 * Świadomie przyjmuje katalog źródłowy jako parametr zamiast na sztywno
 * `base_path()` — testy tych komend operują na kopii appki w katalogu
 * tymczasowym, nigdy na tym repo.
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

    /**
     * Dopisuje obok paczki plik `<nazwa>.sha256` w formacie zgodnym z
     * `sha256sum -c` (hash + dwie spacje + nazwa pliku), żeby admin mógł
     * zweryfikować pobraną paczkę bez przepisywania hasha z konsoli. Tylko
     * dla paczek do dystrybucji (release:build/-hosting) — nie wołane z
     * wewnętrznej migawki kodu robionej przez UpdateService przed apply(),
     * której nikt nie pobiera/weryfikuje ręcznie.
     */
    public function writeChecksumFile(string $zipPath): string
    {
        $hash = hash_file('sha256', $zipPath);
        file_put_contents($zipPath.'.sha256', "{$hash}  ".basename($zipPath)."\n");

        return $hash;
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
