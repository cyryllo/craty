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
 *
 * Fixture w setUp() odtwarza prawdziwy, jedyny układ instalacji appki
 * (patrz CLAUDE.md "Project layout"): `app-storage/` jako prawdziwy
 * storage/ Laravela, i realny symlink `storage` (dokładnie ten, który stawia
 * `artisan storage:link`/samo-naprawiający się kod w index.php) wskazujący
 * na zdjęcia użytkownika.
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
        mkdir($this->appRoot.'/app-storage/app/public', 0755, true);
        mkdir($this->appRoot.'/app-storage/logs', 0755, true);

        file_put_contents($this->appRoot.'/app/placeholder.php', "<?php\n// stary kod\n");
        file_put_contents($this->appRoot.'/.env', "APP_KEY=nie-ruszac\n");
        file_put_contents($this->appRoot.'/app-storage/app/public/user-photo.jpg', 'dane użytkownika');
        file_put_contents($this->appRoot.'/app-storage/logs/laravel.log', 'stary log');
        file_put_contents($this->appRoot.'/VERSION', "1.0.0\n");
        // Prawdziwy symlink, dokładnie jak ten, który stawia storage:link/
        // samo-naprawiający się kod w index.php.
        symlink($this->appRoot.'/app-storage/app/public', $this->appRoot.'/storage');

        config([
            'app.update_root_path' => $this->appRoot,
            'app.version_file_path' => $this->appRoot.'/VERSION',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->appRoot.'/storage'); // symlink najpierw — deleteDirectory() nie przechodzi po nim bezpiecznie
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

    /**
     * Sprawdza jednocześnie chronione ścieżki .env/zdjęć/logów ORAZ sam
     * symlink "storage" — paczka poniżej celowo próbuje nadpisać go zwykłym
     * plikiem (dokładnie tak, jak realnie zgłoszony bug: aktualizacja przez
     * panel na instalacji spłaszczonej "nie wgrała wszystkiego", bo kod
     * kiedyś zakładał inny, klasyczny układ).
     */
    public function test_apply_updates_code_and_protects_env_user_data_and_the_storage_symlink(): void
    {
        $zip = $this->buildPackageZip(['version' => '1.1.0'], [
            'app/placeholder.php' => "<?php\n// nowy kod\n",
            '.env' => 'ATTEMPT-TO-OVERWRITE-ENV',
            'app-storage/app/public/user-photo.jpg' => 'ATTEMPT-TO-OVERWRITE-USER-PHOTO',
            'app-storage/logs/laravel.log' => 'ATTEMPT-TO-OVERWRITE-LOG',
            // Symuluje paczkę, która (błędnie) próbowałaby nadpisać sam
            // symlink zwykłym plikiem — musi zostać pominięta tak samo jak
            // .env, inaczej strona traci dostęp do wszystkich zdjęć/QR.
            'storage' => 'ATTEMPT-TO-OVERWRITE-SYMLINK',
        ]);

        $updates = app(UpdateService::class);
        $manifest = $updates->validatePackage($zip);
        $updates->apply($zip, $manifest);

        $this->assertSame('1.1.0', $updates->currentVersion());
        $this->assertStringContainsString('nowy kod', file_get_contents($this->appRoot.'/app/placeholder.php'));

        // Chronione ścieżki — nietknięte mimo że paczka próbowała je nadpisać.
        $this->assertSame("APP_KEY=nie-ruszac\n", file_get_contents($this->appRoot.'/.env'));
        $this->assertSame('dane użytkownika', file_get_contents($this->appRoot.'/app-storage/app/public/user-photo.jpg'));
        $this->assertSame('stary log', file_get_contents($this->appRoot.'/app-storage/logs/laravel.log'));
        $this->assertTrue(is_link($this->appRoot.'/storage'), 'Symlink "storage" nie powinien zniknąć/zostać nadpisany plikiem.');
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
