<?php

namespace Tests\Feature;

use App\Exceptions\UpdatePackageException;
use App\Services\UpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

/**
 * Wszystkie testy działają na kopii "appki" w katalogu tymczasowym
 * (`$this->appRoot`), nigdy na prawdziwym repo — `UpdateService::apply()`
 * nadpisuje pliki, więc uruchomienie go na realnym drzewie kodu w trakcie
 * testów zepsułoby to repo. `Artisan::call('migrate'/'config:clear'/
 * 'view:clear')` wewnątrz apply() nadal działa na PRAWDZIWEJ (testowej,
 * sqlite) bazie/cache appki — to nieszkodliwe, bo migracje są już zrobione
 * przez RefreshDatabase (więc `migrate --force` to no-op), a `view:clear`
 * tylko każe Blade'owi przekompilować widoki leniwie przy następnym użyciu.
 */
class UpdateServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $appRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appRoot = sys_get_temp_dir().'/craty-update-test-'.uniqid();
        mkdir($this->appRoot.'/app', 0755, true);
        mkdir($this->appRoot.'/storage/app/public', 0755, true);
        mkdir($this->appRoot.'/storage/logs', 0755, true);

        file_put_contents($this->appRoot.'/app/placeholder.php', "<?php\n// stary kod\n");
        file_put_contents($this->appRoot.'/.env', "APP_KEY=nie-ruszac\n");
        file_put_contents($this->appRoot.'/storage/app/public/user-photo.jpg', 'dane użytkownika');
        file_put_contents($this->appRoot.'/storage/logs/laravel.log', 'stary log');
        file_put_contents($this->appRoot.'/VERSION', "1.0.0\n");

        config([
            'app.update_root_path' => $this->appRoot,
            'app.version_file_path' => $this->appRoot.'/VERSION',
        ]);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->appRoot);
        parent::tearDown();
    }

    public function test_validate_package_rejects_a_version_that_is_not_newer(): void
    {
        $zip = $this->buildPackageZip(['version' => '1.0.0']);

        $this->expectException(UpdatePackageException::class);
        app(UpdateService::class)->validatePackage($zip);
    }

    public function test_validate_package_rejects_wrong_checksum(): void
    {
        $zip = $this->buildPackageZip(['version' => '1.1.0']);

        $this->expectException(UpdatePackageException::class);
        app(UpdateService::class)->validatePackage($zip, 'not-the-real-checksum');
    }

    public function test_validate_package_accepts_correct_checksum(): void
    {
        $zip = $this->buildPackageZip(['version' => '1.1.0']);

        $manifest = app(UpdateService::class)->validatePackage($zip, hash_file('sha256', $zip));

        $this->assertSame('1.1.0', $manifest['version']);
    }

    public function test_validate_package_rejects_missing_manifest(): void
    {
        $zip = tempnam(sys_get_temp_dir(), 'craty-pkg-').'.zip';
        $archive = new ZipArchive();
        $archive->open($zip, ZipArchive::CREATE);
        $archive->addFromString('app/placeholder.php', '<?php // nowy kod');
        $archive->close();

        $this->expectException(UpdatePackageException::class);
        app(UpdateService::class)->validatePackage($zip);
    }

    public function test_validate_package_rejects_a_version_below_min_version(): void
    {
        $zip = $this->buildPackageZip(['version' => '3.0.0', 'min_version' => '2.0.0']);

        $this->expectException(UpdatePackageException::class);
        app(UpdateService::class)->validatePackage($zip);
    }

    public function test_apply_updates_code_and_protects_env_and_user_data(): void
    {
        $zip = $this->buildPackageZip(['version' => '1.1.0'], [
            'app/placeholder.php' => "<?php\n// nowy kod\n",
            '.env' => 'ATTEMPT-TO-OVERWRITE-ENV',
            'storage/app/public/user-photo.jpg' => 'ATTEMPT-TO-OVERWRITE-USER-PHOTO',
        ]);

        $updates = app(UpdateService::class);
        $manifest = $updates->validatePackage($zip);
        $updates->apply($zip, $manifest);

        $this->assertSame('1.1.0', $updates->currentVersion());
        $this->assertStringContainsString('nowy kod', file_get_contents($this->appRoot.'/app/placeholder.php'));

        // Chronione ścieżki — nietknięte mimo że paczka próbowała je nadpisać.
        $this->assertSame("APP_KEY=nie-ruszac\n", file_get_contents($this->appRoot.'/.env'));
        $this->assertSame('dane użytkownika', file_get_contents($this->appRoot.'/storage/app/public/user-photo.jpg'));
    }

    public function test_apply_rejects_zip_slip_path_traversal(): void
    {
        $zip = tempnam(sys_get_temp_dir(), 'craty-pkg-').'.zip';
        $archive = new ZipArchive();
        $archive->open($zip, ZipArchive::CREATE);
        $archive->addFromString('update-manifest.json', json_encode(['version' => '1.1.0']));
        $archive->addFromString('../../evil.php', '<?php // haker był tu');
        $archive->close();

        $updates = app(UpdateService::class);
        $manifest = $updates->validatePackage($zip);

        $this->expectException(UpdatePackageException::class);
        $updates->apply($zip, $manifest);
    }

    /**
     * Regresja realnie zgłoszona przez użytkownika: aktualizacja przez panel
     * na instalacji spłaszczonej (release:build-hosting, open_basedir
     * ograniczony do document rootu) "nie wgrała wszystkiego" — bo
     * UpdateService zakładał wyłącznie klasyczny układ (storage/, nie
     * app-storage/). Ten test buduje WŁASNY, osobny katalog symulujący
     * prawdziwą instalację spłaszczoną (z realnym symlinkiem "storage",
     * dokładnie jak na produkcji), nie używa współdzielonego $this->appRoot
     * z setUp() (ten jest na sztywno klasyczny).
     */
    public function test_apply_on_a_flattened_install_protects_app_storage_and_the_storage_symlink(): void
    {
        $flatRoot = sys_get_temp_dir().'/craty-update-flat-test-'.uniqid();
        mkdir($flatRoot.'/app', 0755, true);
        mkdir($flatRoot.'/app-storage/app/public', 0755, true);
        mkdir($flatRoot.'/app-storage/logs', 0755, true);
        file_put_contents($flatRoot.'/app/placeholder.php', "<?php\n// stary kod\n");
        file_put_contents($flatRoot.'/.env', "APP_KEY=nie-ruszac\n");
        file_put_contents($flatRoot.'/app-storage/app/public/real-photo.jpg', 'prawdziwe zdjęcie użytkownika');
        file_put_contents($flatRoot.'/app-storage/logs/laravel.log', 'stary log');
        file_put_contents($flatRoot.'/VERSION', "1.0.0\n");
        // Prawdziwy symlink, dokładnie jak ten, który stawia storage:link/
        // samo-naprawiający się kod w index.php spłaszczonej paczki.
        symlink($flatRoot.'/app-storage/app/public', $flatRoot.'/storage');

        config([
            'app.update_root_path' => $flatRoot,
            'app.version_file_path' => $flatRoot.'/VERSION',
        ]);

        $zip = $this->buildPackageZip(['version' => '1.1.0'], [
            'app/placeholder.php' => "<?php\n// nowy kod\n",
            '.env' => 'ATTEMPT-TO-OVERWRITE-ENV',
            'app-storage/app/public/real-photo.jpg' => 'ATTEMPT-TO-OVERWRITE-PHOTO',
            'app-storage/logs/laravel.log' => 'ATTEMPT-TO-OVERWRITE-LOG',
            // Symuluje paczkę, która (błędnie) próbowałaby nadpisać sam
            // symlink zwykłym plikiem — musi zostać pominięta tak samo jak
            // .env, inaczej strona traci dostęp do wszystkich zdjęć/QR.
            'storage' => 'ATTEMPT-TO-OVERWRITE-SYMLINK',
        ]);

        try {
            $updates = app(UpdateService::class);
            $this->assertTrue($updates->isFlattened());

            $manifest = $updates->validatePackage($zip);
            $updates->apply($zip, $manifest);

            $this->assertSame('1.1.0', $updates->currentVersion());
            $this->assertStringContainsString('nowy kod', file_get_contents($flatRoot.'/app/placeholder.php'));

            // Chronione ścieżki spłaszczonego układu — nietknięte mimo że paczka próbowała je nadpisać.
            $this->assertSame("APP_KEY=nie-ruszac\n", file_get_contents($flatRoot.'/.env'));
            $this->assertSame('prawdziwe zdjęcie użytkownika', file_get_contents($flatRoot.'/app-storage/app/public/real-photo.jpg'));
            $this->assertSame('stary log', file_get_contents($flatRoot.'/app-storage/logs/laravel.log'));
            $this->assertTrue(is_link($flatRoot.'/storage'), 'Symlink "storage" nie powinien zniknąć/zostać nadpisany plikiem.');
        } finally {
            @unlink($flatRoot.'/storage'); // symlink najpierw — deleteDirectory() nie przechodzi po nim bezpiecznie
            $this->deleteDirectory($flatRoot);
        }
    }

    /** @param  array<string, mixed>  $manifest */
    private function buildPackageZip(array $manifest, array $files = []): string
    {
        $zip = tempnam(sys_get_temp_dir(), 'craty-pkg-').'.zip';
        $archive = new ZipArchive();
        $archive->open($zip, ZipArchive::CREATE);
        $archive->addFromString('update-manifest.json', json_encode($manifest));

        foreach ($files as $path => $contents) {
            $archive->addFromString($path, $contents);
        }

        if (empty($files)) {
            $archive->addFromString('app/placeholder.php', "<?php\n// nowy kod\n");
        }

        $archive->close();

        return $zip;
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
