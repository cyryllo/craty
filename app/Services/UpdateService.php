<?php

namespace App\Services;

use App\Exceptions\UpdatePackageException;
use App\Support\AppVersion;
use App\Support\UpdatePaths;
use FilesystemIterator;
use Illuminate\Support\Facades\Artisan;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use ZipArchive;

/**
 * Rozpakowuje i stosuje wgraną paczkę aktualizacji "na żywo" (patrz TODO.md
 * "Moduł Aktualizacje"). Świadomie NIE dotyka bazy danych — ani migracji
 * (`migrate --force` tylko dogrywa nowe), ani jej ewentualnego cofnięcia,
 * bo automatyczne cofanie dowolnych migracji nie jest ogólnie bezpieczne;
 * panel zaleca zrobienie backupu bazy samemu przed aktualizacją, która
 * dodaje migracje. `appRoot()` jest konfigurowalny (nie zawsze
 * `base_path()`), żeby testy operowały na kopii appki w katalogu
 * tymczasowym, nigdy na tym repo.
 *
 * **Świadomie bez cofania aktualizacji (rollback)** — było to wcześniej
 * osobną funkcją (migawka całego kodu brana przed każdym `apply()`,
 * przywracana przyciskiem "Wycofaj"), usuniętą na wyraźną prośbę: dodatkowa
 * złożoność (migawka, stan na dysku, druga uprzywilejowana trasa z
 * `password.confirm`) bez realnej potrzeby korzystania z niej. Jeśli
 * aktualizacja pójdzie źle, powrót do poprzedniej wersji to wgranie
 * poprzedniej paczki z manualnym przywróceniem bazy z backupu zrobionego
 * przed aktualizacją — tak samo jak zawsze było zalecane robić z bazą,
 * teraz spójnie i dla kodu.
 *
 * **Jeden, stały, spłaszczony układ instalacji** (patrz CLAUDE.md "Project
 * layout") — appka nie ma osobnego document rootu, prawdziwy `storage/`
 * Laravela nazywa się na stałe `app-storage/`, a `storage` w korzeniu to
 * zawsze SYMLINK do `app-storage/app/public` (`artisan storage:link`).
 * Wcześniej appka umiała też działać w klasycznym układzie (osobny
 * `public/`) i auto-wykrywała, z którym ma do czynienia — usunięte na
 * wyraźną prośbę, żeby nie trzeba było utrzymywać dwóch wariantów
 * jednocześnie (dev, hosting, paczki) — patrz TODO.md/CHANGELOG.md.
 */
class UpdateService
{
    public function __construct(
        private readonly AppVersion $version,
    ) {
    }

    public function appRoot(): string
    {
        return config('app.update_root_path', base_path());
    }

    public function currentVersion(): string
    {
        return $this->version->current();
    }

    /**
     * Sprawdza paczkę bez dotykania czegokolwiek na dysku appki — sama
     * suma kontrolna (jeśli podana) i manifest muszą się zgadzać, zanim
     * apply() w ogóle zacznie coś nadpisywać.
     *
     * @return array<string, mixed> manifest paczki
     */
    public function validatePackage(string $zipPath, ?string $expectedChecksum = null): array
    {
        if (filled($expectedChecksum)) {
            $actual = hash_file('sha256', $zipPath);

            if (! $actual || ! hash_equals(strtolower(trim($expectedChecksum)), $actual)) {
                throw new UpdatePackageException(__('The uploaded file does not match the expected checksum.'));
            }
        }

        $manifest = $this->readManifest($zipPath);
        $current = $this->currentVersion();

        if (version_compare($manifest['version'], $current, '<=')) {
            throw new UpdatePackageException(__('This package (:package) is not newer than the installed version (:current).', [
                'package' => $manifest['version'], 'current' => $current,
            ]));
        }

        if (! empty($manifest['min_version']) && version_compare($current, $manifest['min_version'], '<')) {
            throw new UpdatePackageException(__('This package requires at least version :min — update in smaller steps first.', [
                'min' => $manifest['min_version'],
            ]));
        }

        return $manifest;
    }

    /** @param  array<string, mixed>  $manifest */
    public function apply(string $zipPath, array $manifest): void
    {
        $root = $this->appRoot();

        $this->ensureDirectory($this->workDir());

        // Świadomie BRAK backupu tutaj — appka nie ma już własnego modułu
        // Backup (usunięty, patrz TODO.md/CHANGELOG.md). Rekomendacja
        // zrobienia kopii samemu jest tylko w UI (settings/updates.blade.php).

        // 1. Rozpakuj nową paczkę do katalogu tymczasowego.
        $extractDir = $this->tmpDir().'/extract-'.now()->format('Y-m-d-His');
        $this->extractSafely($zipPath, $extractDir);

        try {
            // 2. Podmiana plików "na żywo", z pominięciem chronionych ścieżek.
            $this->copyInto($extractDir, $root, UpdatePaths::PROTECTED_PATHS);

            // 3. Migracje z paczki (nowe zostaną wykonane, reszta pominięta).
            Artisan::call('migrate', ['--force' => true]);

            // 4. Nowa wersja + czyszczenie cache configu/widoków.
            $this->version->set($manifest['version']);
            Artisan::call('config:clear');
            Artisan::call('view:clear');
        } finally {
            $this->deleteDirectory($extractDir);
        }
    }

    /** @return array<string, mixed> */
    private function readManifest(string $zipPath): array
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new UpdatePackageException(__('The uploaded file is not a valid zip archive.'));
        }

        $manifestJson = $zip->getFromName('update-manifest.json');
        $zip->close();

        if ($manifestJson === false) {
            throw new UpdatePackageException(__('The package is missing update-manifest.json.'));
        }

        $manifest = json_decode($manifestJson, true);

        if (! is_array($manifest) || empty($manifest['version'])) {
            throw new UpdatePackageException(__('The update manifest is invalid.'));
        }

        return $manifest;
    }

    /** Katalog roboczy modułu (rozpakowywanie paczki przed podmianą plików) — pod prawdziwym storage/ appki (app-storage/). */
    private function workDir(): string
    {
        return $this->appRoot().'/app-storage/app/updates';
    }

    private function tmpDir(): string
    {
        return $this->workDir().'/tmp';
    }

    private function ensureDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /** Rozpakowuje ręcznie (nie ZipArchive::extractTo()), żeby jawnie odrzucić "zip slip" — wpisy z "../" wychodzące poza katalog docelowy. */
    private function extractSafely(string $zipPath, string $targetDir): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new UpdatePackageException(__('The uploaded file is not a valid zip archive.'));
        }

        $this->ensureDirectory($targetDir);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $relative = $this->sanitizeEntryName($name);
            $destination = $targetDir.'/'.$relative;

            if (str_ends_with($name, '/')) {
                $this->ensureDirectory($destination);

                continue;
            }

            $this->ensureDirectory(dirname($destination));
            file_put_contents($destination, $zip->getFromIndex($i));
        }

        $zip->close();
    }

    private function sanitizeEntryName(string $name): string
    {
        $normalized = ltrim(str_replace('\\', '/', $name), '/');

        if (preg_match('#(^|/)\.\.(/|$)#', $normalized)) {
            throw new UpdatePackageException(__('The package contains an unsafe file path: :name', ['name' => $name]));
        }

        return $normalized;
    }

    /** @param  array<int, string>  $excludes */
    private function copyInto(string $source, string $destination, array $excludes): void
    {
        $source = rtrim($source, '/');

        $filter = new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            fn (SplFileInfo $current) => ! $this->isExcluded($this->relativePath($source, $current), $excludes)
        );

        $iterator = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            $target = $destination.'/'.$this->relativePath($source, $file);

            if ($file->isDir()) {
                $this->ensureDirectory($target);
            } else {
                $this->ensureDirectory(dirname($target));
                copy($file->getPathname(), $target);
            }
        }
    }

    private function relativePath(string $base, SplFileInfo $file): string
    {
        return ltrim(substr($file->getPathname(), strlen($base)), '/');
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
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($path);
    }
}
