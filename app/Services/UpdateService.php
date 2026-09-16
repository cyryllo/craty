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
 * poprzedniej paczki `-full`/`-hosting` z manualnym przywróceniem bazy z
 * backupu zrobionego przed aktualizacją — tak samo jak zawsze było zalecane
 * robić z bazą, teraz spójnie i dla kodu.
 *
 * **Świadomy zarówno klasycznego, jak i spłaszczonego układu instalacji**
 * (patrz `App\Console\Commands\BuildHostingPackage` — hosting z
 * `open_basedir` ograniczonym do document rootu, gdzie prawdziwy `storage/`
 * appki nazywa się `app-storage/`, a `storage` pod document rootem to
 * SYMLINK). Wykryte automatycznie po istnieniu katalogu `app-storage` w
 * korzeniu appki (`isFlattened()`) — admin nie zaznacza niczego w panelu,
 * po prostu wgrywa paczkę zbudowaną dla swojego układu
 * (`release:build`/`release:build-hosting`). Bez tego rozróżnienia
 * (znalezione po realnym zgłoszeniu: aktualizacja przez panel na
 * spłaszczonej instalacji "wgrała nie wszystko") paczka klasyczna wgrana
 * na spłaszczoną instalację ląduje pod BŁĘDNYMI ścieżkami (`public/build/
 * ...` zamiast `build/...`, bo klasyczna paczka ma `public/` jako osobny
 * katalog, którego na spłaszczonej instalacji w ogóle nie ma) — kod PHP/
 * widoki (leżące na tym samym poziomie w obu układach) aktualizują się
 * poprawnie, ale skompilowane assety (CSS/JS) nie, bo trafiają do
 * nieużywanego, osieroconego podkatalogu zamiast nadpisać prawdziwe pliki.
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

    /** Instalacja spłaszczona (release:build-hosting) trzyma prawdziwy storage/ pod inną nazwą, patrz storageDirName(). */
    public function isFlattened(): bool
    {
        return is_dir($this->appRoot().'/app-storage');
    }

    /** "app-storage" na instalacji spłaszczonej, inaczej zwykłe "storage" — jedno miejsce, z którego korzysta workDir(). */
    private function storageDirName(): string
    {
        return $this->isFlattened() ? 'app-storage' : 'storage';
    }

    /** Krótsza lista ścieżek nigdy nienadpisywanych — dobrana do wykrytego układu instalacji, patrz komentarz klasy. */
    private function protectedPaths(): array
    {
        return $this->isFlattened() ? UpdatePaths::PROTECTED_PATHS_FLATTENED : UpdatePaths::PROTECTED_PATHS;
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

        // Świadomie BRAK automatycznego backupu tutaj — była to wcześniej
        // twarda blokada (nieudany backup przerywał całą aktualizację), ale
        // realny przypadek pokazał, że na części hostingów backup bazy
        // strukturalnie nie może się udać (np. `proc_open` zablokowane przez
        // hosting — spatie/laravel-backup zawsze woła prawdziwy `mysqldump`
        // przez `Symfony\Process`, więc żaden retry tego nie naprawi) — taki
        // admin był trwale zablokowany, bez możliwości wgrania NAWET
        // poprawki naprawiającej samą diagnostykę backupu, bo aktualizacja
        // przez panel to właśnie ten sam zablokowany mechanizm. Zamiast
        // twardego wymogu: rekomendacja ręcznego backupu w UI (patrz
        // settings/updates.blade.php) — admin decyduje sam, backup bazy
        // przez Ustawienia → Kopie zapasowe (jeśli działa) albo eksport z
        // panelu hostingu, PRZED kliknięciem "Zastosuj aktualizację".

        // 1. Rozpakuj nową paczkę do katalogu tymczasowego.
        $extractDir = $this->tmpDir().'/extract-'.now()->format('Y-m-d-His');
        $this->extractSafely($zipPath, $extractDir);

        try {
            // 2. Podmiana plików "na żywo", z pominięciem chronionych ścieżek.
            $this->copyInto($extractDir, $root, $this->protectedPaths());

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

    /** Katalog roboczy modułu (rozpakowywanie paczki przed podmianą plików) — pod prawdziwym storage/ appki, dobranym do wykrytego układu instalacji. */
    private function workDir(): string
    {
        return $this->appRoot().'/'.$this->storageDirName().'/app/updates';
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
