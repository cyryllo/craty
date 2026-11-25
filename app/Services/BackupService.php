<?php

namespace App\Services;

use App\Exceptions\BackupException;
use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Support\AppVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Kopie zapasowe z panelu, w czystym PHP: bez mysqldump i bez proc_open,
 * które na części hostingów są zablokowane (to zabiło poprzedni moduł kopii,
 * patrz CLAUDE.md "No Backup module"). Kopia to jeden .zip:
 *
 *   manifest.json   wersja Craty, sterownik bazy, data, rodzaj kopii
 *   database.sql    zrzut bazy (DROP/CREATE/INSERT), do zaimportowania także ręcznie
 *   files/...       zawartość dysku "public" (zdjęcia, załączniki, kody QR, logo)
 *   CZYTAJ-MNIE.txt jak odtworzyć kopię ręcznie
 *
 * Kopie leżą w app-storage/app/backups, niedostępnym z internetu (.htaccess
 * blokuje ^/app-storage/, a sam katalog dostaje jeszcze własne "deny").
 */
class BackupService
{
    /** Koniec każdej instrukcji w database.sql: przywracanie dzieli plik po tej linii. */
    public const STATEMENT_END = '-- craty:end';

    /** Tabele zapisywane bez danych (sesje, cache, kolejki, tokeny resetu hasła). */
    private const STRUCTURE_ONLY = ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'];

    public const TYPES = [
        'manual' => '',
        'auto' => '-auto',
        'pre-restore' => '-przed-przywroceniem',
        'uploaded' => '-wgrana',
    ];

    /** Do wyboru w ustawieniach (dni → tekst źródłowy do __()); 0 = bez limitu. */
    public const RETENTION_OPTIONS = [7 => '7 days', 14 => '14 days', 30 => '30 days', 90 => '90 days', 0 => 'no limit'];

    public const SCHEDULES = ['off' => 'Off', 'daily' => 'Every day', 'weekly' => 'Every week'];

    private const NAME_PATTERN = '/^craty-kopia-\d{4}-\d{2}-\d{2}-\d{6}(-auto|-przed-przywroceniem|-wgrana)?\.zip$/';

    public function __construct(private readonly AppVersion $version)
    {
    }

    public function dir(): string
    {
        $dir = config('app.backup_path') ?: storage_path('app/backups');
        File::ensureDirectoryExists($dir, 0700);
        // Druga linia obrony obok reguły w głównym .htaccess.
        if (! File::exists($dir.'/.htaccess')) {
            File::put($dir.'/.htaccess', "Require all denied\n");
        }

        return $dir;
    }

    /** @return list<array{name: string, size: int, created: Carbon, type: string}> */
    public function list(): array
    {
        $backups = [];
        foreach (File::files($this->dir()) as $file) {
            if (! preg_match(self::NAME_PATTERN, $file->getFilename(), $m)) {
                continue;
            }
            $backups[] = [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'created' => Carbon::createFromTimestamp($file->getMTime(), config('app.timezone')),
                'type' => array_search($m[1] ?? '', self::TYPES, true) ?: 'manual',
            ];
        }
        usort($backups, fn ($a, $b) => $b['created'] <=> $a['created'] ?: strcmp($b['name'], $a['name']));

        return $backups;
    }

    /** Pełna ścieżka kopii o tej nazwie; tylko nazwy w formacie Craty (bez ../ i innych sztuczek). */
    public function path(string $name): string
    {
        $path = $this->dir().'/'.$name;
        if (! preg_match(self::NAME_PATTERN, $name) || ! File::isFile($path)) {
            throw new BackupException(__('There is no such backup.'));
        }

        return $path;
    }

    public function create(string $type = 'manual'): string
    {
        $this->unlimited();
        $name = 'craty-kopia-'.now()->format('Y-m-d-His').self::TYPES[$type].'.zip';
        $path = $this->dir().'/'.$name;
        $sql = tempnam(sys_get_temp_dir(), 'craty-sql');

        try {
            $this->dumpDatabase($sql);

            $zip = new ZipArchive;
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new BackupException(__('Could not create the backup file.'));
            }
            $zip->addFromString('manifest.json', json_encode([
                'app' => 'craty',
                'version' => $this->version->current(),
                'driver' => DB::getDriverName(),
                'created_at' => now()->toIso8601String(),
                'type' => $type,
            ], JSON_PRETTY_PRINT));
            $zip->addFile($sql, 'database.sql');
            $zip->addFromString('CZYTAJ-MNIE.txt', $this->readme());

            $public = Storage::disk('public')->path('');
            foreach (File::allFiles($public) as $file) {
                if (! $file->isLink()) {
                    $zip->addFile($file->getPathname(), 'files/'.str_replace('\\', '/', $file->getRelativePathname()));
                }
            }
            if (! $zip->close()) {
                throw new BackupException(__('Could not create the backup file.'));
            }
        } catch (\Throwable $e) {
            File::delete($path);
            throw $e instanceof BackupException ? $e : new BackupException(__('Backup failed: :error', ['error' => $e->getMessage()]), 0, $e);
        } finally {
            File::delete($sql);
        }

        ActivityLog::record('backup.created', null, ['name' => $name, 'type' => $type]);
        $this->prune();

        return $name;
    }

    public function delete(string $name): void
    {
        File::delete($this->path($name));
        ActivityLog::record('backup.deleted', null, ['name' => $name]);
    }

    /** Usuwa kopie starsze niż ustawiony czas, ale zawsze zostawia najnowszą. */
    public function prune(): int
    {
        $days = (int) (AppSetting::current()->backup_retention_days ?? 14);
        if ($days === 0) {
            return 0;
        }

        $deleted = 0;
        foreach (array_slice($this->list(), 1) as $backup) {
            if ($backup['created']->lt(now()->subDays($days))) {
                File::delete($this->dir().'/'.$backup['name']);
                $deleted++;
            }
        }

        return $deleted;
    }

    /** Czy pora na kopię automatyczną (codzienną albo cotygodniową) według ustawień. */
    public function automaticDue(): bool
    {
        $interval = match (AppSetting::current()->backup_schedule) {
            'daily' => now()->subHours(20),
            'weekly' => now()->subDays(6)->subHours(20),
            default => null,
        };
        if (! $interval) {
            return false;
        }
        $last = collect($this->list())->firstWhere('type', 'auto');

        return ! $last || $last['created']->lt($interval);
    }

    /** Wgranie kopii z komputera (np. z innej instalacji) na listę; przywraca się ją osobno. */
    public function import(UploadedFile $file): string
    {
        $manifest = $this->readManifest($file->getRealPath());
        $name = 'craty-kopia-'.now()->format('Y-m-d-His').self::TYPES['uploaded'].'.zip';
        $file->move($this->dir(), $name);
        ActivityLog::record('backup.uploaded', null, ['name' => $name, 'version' => $manifest['version'] ?? null]);

        return $name;
    }

    /**
     * Przywraca kopię: najpierw kopia bieżącego stanu, potem baza (wszystkie
     * tabele usunięte i odtworzone z kopii, następnie migracje do bieżącej
     * wersji) i pliki. Zwraca nazwę kopii bezpieczeństwa.
     */
    public function restore(string $name, ?string $actorName = null): string
    {
        $this->unlimited();
        $path = $this->path($name);
        $manifest = $this->readManifest($path);
        if (($manifest['driver'] ?? null) !== DB::getDriverName()) {
            throw new BackupException(__('This backup comes from a different database type and can\'t be restored here.'));
        }
        if (version_compare((string) ($manifest['version'] ?? '0'), $this->version->current(), '>')) {
            throw new BackupException(__('This backup comes from a newer Craty version (:version). Update Craty first.', ['version' => $manifest['version']]));
        }

        $safety = $this->create('pre-restore');

        $work = storage_path('app/backup-temp/restore-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($work);
        try {
            $this->extract($path, $work);
            $this->importDatabase($work.'/database.sql');
            Artisan::call('migrate', ['--force' => true]);
            $this->replacePublicFiles($work.'/files');
            Artisan::call('view:clear');
        } catch (\Throwable $e) {
            throw $e instanceof BackupException ? $e : new BackupException(__('Restore failed: :error. A backup of the state before restoring is on the list: :name', ['error' => $e->getMessage(), 'name' => $safety]), 0, $e);
        } finally {
            File::deleteDirectory($work);
        }

        // Po przywróceniu tabela kont pochodzi z kopii: wpis bez powiązania z kontem.
        ActivityLog::record('backup.restored', null, ['name' => $name, 'safety' => $safety], null, $actorName);

        return $safety;
    }

    // ---------------------------------------------------------------------

    private function dumpDatabase(string $target): void
    {
        $out = fopen($target, 'w');
        $driver = DB::getDriverName();
        $pdo = DB::connection()->getPdo();
        $q = fn (string $name) => $driver === 'mysql' || $driver === 'mariadb' ? '`'.str_replace('`', '``', $name).'`' : '"'.str_replace('"', '""', $name).'"';
        $statement = fn (string $sql) => fwrite($out, $sql.";\n".self::STATEMENT_END."\n");

        fwrite($out, '-- Craty '.$this->version->current().', '.now()->toDateTimeString().", {$driver}\n");
        if ($driver === 'sqlite') {
            $statement('PRAGMA defer_foreign_keys = ON');
        } else {
            $statement('SET NAMES utf8mb4');
            $statement('SET FOREIGN_KEY_CHECKS = 0');
        }

        // Najpierw struktura wszystkich tabel, potem dane: wiersz z kluczem obcym
        // trafia wtedy zawsze do bazy, w której istnieje już tabela, do której się odwołuje.
        foreach ($this->tables() as $table) {
            $create = $driver === 'sqlite'
                ? DB::selectOne("select sql from sqlite_master where type = 'table' and name = ?", [$table])->sql
                : ((array) DB::selectOne('SHOW CREATE TABLE '.$q($table)))['Create Table'];
            $statement('DROP TABLE IF EXISTS '.$q($table));
            $statement($create);
        }

        foreach ($this->tables() as $table) {
            if (in_array($table, self::STRUCTURE_ONLY, true)) {
                continue;
            }

            $rows = [];
            $columns = null;
            foreach (DB::table($table)->cursor() as $row) {
                $row = (array) $row;
                $columns ??= implode(', ', array_map($q, array_keys($row)));
                $rows[] = '('.implode(', ', array_map(fn ($v) => match (true) {
                    $v === null => 'NULL',
                    is_int($v), is_float($v) => (string) $v,
                    is_bool($v) => $v ? '1' : '0',
                    default => $pdo->quote((string) $v),
                }, $row)).')';
                if (count($rows) === 100) {
                    $statement('INSERT INTO '.$q($table)." ({$columns}) VALUES\n".implode(",\n", $rows));
                    $rows = [];
                }
            }
            if ($rows) {
                $statement('INSERT INTO '.$q($table)." ({$columns}) VALUES\n".implode(",\n", $rows));
            }
        }

        if ($driver !== 'sqlite') {
            $statement('SET FOREIGN_KEY_CHECKS = 1');
        }
        fclose($out);
    }

    /** @return list<string> */
    private function tables(): array
    {
        return collect(Schema::getTables())
            ->pluck('name')
            ->reject(fn ($name) => str_starts_with($name, 'sqlite_'))
            ->values()->all();
    }

    private function importDatabase(string $file): void
    {
        if (! File::isFile($file)) {
            throw new BackupException(__('The backup has no database.'));
        }

        // Najpierw wszystkie obecne tabele, także te, których w kopii nie ma
        // (nowsza wersja): inaczej migracje po imporcie trafiłyby na istniejące tabele.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA defer_foreign_keys = ON');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        }
        $this->dropAllTables();

        $in = fopen($file, 'r');
        $buffer = '';
        while (($line = fgets($in)) !== false) {
            if (rtrim($line, "\r\n") === self::STATEMENT_END) {
                $sql = rtrim(trim($buffer), ';');
                if ($sql !== '') {
                    DB::unprepared($sql);
                }
                $buffer = '';

                continue;
            }
            // Komentarz przed instrukcją (nagłówek pliku); wewnątrz wartości nie
            // trafimy tu, bo bufor nie jest wtedy pusty.
            if ($buffer === '' && str_starts_with($line, '--')) {
                continue;
            }
            $buffer .= $line;
        }
        fclose($in);

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    /**
     * MySQL/MariaDB: kolejność bez znaczenia (FOREIGN_KEY_CHECKS = 0). SQLite
     * (testy) sprawdza powiązania przy DROP i nie da się tego wyłączyć w
     * transakcji, więc usuwamy w kolejnych przebiegach: najpierw to, co
     * się da, aż nie zostanie nic.
     */
    private function dropAllTables(): void
    {
        $left = $this->tables();
        while ($left) {
            $failed = [];
            foreach ($left as $table) {
                try {
                    Schema::drop($table);
                } catch (\Illuminate\Database\QueryException $e) {
                    $failed[] = $table;
                    $error = $e;
                }
            }
            if (count($failed) === count($left)) {
                throw $error;
            }
            $left = $failed;
        }
    }

    private function replacePublicFiles(string $source): void
    {
        $public = Storage::disk('public')->path('');
        File::ensureDirectoryExists($public);
        foreach (File::directories($public) as $dir) {
            File::deleteDirectory($dir);
        }
        foreach (File::files($public, true) as $file) {
            if ($file->getFilename() !== '.gitignore') {
                File::delete($file->getPathname());
            }
        }
        if (File::isDirectory($source)) {
            File::copyDirectory($source, $public);
        }
    }

    /** Rozpakowanie wpis po wpisie: nazwy z "..", ścieżki bezwzględne i spoza files/ są odrzucane. */
    private function extract(string $zipPath, string $target): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new BackupException(__('This is not a Craty backup.'));
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (str_ends_with($entry, '/')) {
                continue;
            }
            $safe = ! str_contains($entry, '\\') && ! str_starts_with($entry, '/')
                && ! in_array('..', explode('/', $entry), true)
                && ($entry === 'database.sql' || str_starts_with($entry, 'files/'));
            if (! $safe) {
                continue;
            }
            File::ensureDirectoryExists(dirname($target.'/'.$entry));
            $from = $zip->getStream($entry);
            $to = fopen($target.'/'.$entry, 'w');
            stream_copy_to_stream($from, $to);
            fclose($from);
            fclose($to);
        }
        $zip->close();
    }

    private function readManifest(string $zipPath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new BackupException(__('This is not a Craty backup.'));
        }
        $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
        $hasDatabase = $zip->locateName('database.sql') !== false;
        $zip->close();

        if (! is_array($manifest) || ($manifest['app'] ?? null) !== 'craty' || ! $hasDatabase) {
            throw new BackupException(__('This is not a Craty backup.'));
        }

        return $manifest;
    }

    /**
     * Bez limitu czasu i bez przerywania, gdy przeglądarka się rozłączy (np.
     * "zmiana sieci" w trakcie długiego żądania): przywracanie przerwane w
     * połowie zostawiłoby bazę bez części tabel.
     */
    private function unlimited(): void
    {
        ignore_user_abort(true);
        if (function_exists('set_time_limit') && ! in_array('set_time_limit', array_map('trim', explode(',', (string) ini_get('disable_functions'))), true)) {
            @set_time_limit(0);
        }
    }

    private function readme(): string
    {
        return <<<'TXT'
        Kopia zapasowa Craty
        ====================

        Najprościej przywrócić ją w samym Craty: Ustawienia → Kopie zapasowe →
        „Wgraj kopię”, a potem „Przywróć” przy tej kopii.

        Ręcznie (np. na nowym serwerze):
        1. Zainstaluj Craty w tej samej albo nowszej wersji (manifest.json mówi,
           z której wersji jest kopia).
        2. Zaimportuj database.sql do bazy danych (np. w phpMyAdmin: Import).
        3. Skopiuj zawartość katalogu files/ do app-storage/app/public/.
        4. Hasło do poczty SMTP jest zaszyfrowane kluczem APP_KEY z pliku .env
           starej instalacji; bez tego klucza wpisz je ponownie w Ustawienia → Poczta.

        Craty backup: restore it in Settings → Backups ("Upload backup", then
        "Restore"), or by hand: import database.sql, copy files/ into
        app-storage/app/public/, and re-enter the SMTP password if APP_KEY changed.
        TXT;
    }
}
