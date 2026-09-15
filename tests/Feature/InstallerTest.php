<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    use RefreshDatabase;

    // Ten test celowo sprawdza stan "przed instalacją" — patrz Tests\TestCase.
    protected bool $withoutDefaultInstalledUser = true;

    private string $tempEnvPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Instalator pisze do "swojego" .env — nigdy do prawdziwego pliku
        // tego repo. Patrz AppServiceProvider::register() (wiązanie EnvFileWriter).
        $this->tempEnvPath = tempnam(sys_get_temp_dir(), 'craty-install-test-env-');
        config(['app.env_file_path' => $this->tempEnvPath]);
    }

    protected function tearDown(): void
    {
        @unlink($this->tempEnvPath);
        parent::tearDown();
    }

    public function test_installer_shows_requirements_when_not_installed(): void
    {
        // Świeża baza testowa (RefreshDatabase już zmigrowała, ale zero userów)
        // to dokładnie stan "jeszcze niezainstalowane".
        $this->get(route('install.show'))
            ->assertOk()
            ->assertSee('PHP');
    }

    public function test_installer_redirects_to_login_when_already_installed(): void
    {
        User::factory()->create(['role' => 'admin']);

        $this->get(route('install.show'))->assertRedirect(route('login'));
        $this->post(route('install.store'))->assertRedirect(route('login'));
    }

    public function test_test_database_endpoint_succeeds_with_real_credentials(): void
    {
        // Kontener testowy ma sieciowy dostęp do usługi "db" z docker-compose —
        // te same dane co dev-owy MariaDB, żadnego zapisu, tylko otwarcie i
        // odrzucenie połączenia PDO.
        $response = $this->postJson(route('install.test-database'), [
            'db_host' => 'db',
            'db_port' => 3306,
            'db_database' => 'graty',
            'db_username' => 'graty',
            'db_password' => 'graty',
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
    }

    public function test_test_database_endpoint_fails_with_bad_credentials(): void
    {
        $response = $this->postJson(route('install.test-database'), [
            'db_host' => 'db',
            'db_port' => 3306,
            'db_database' => 'graty',
            'db_username' => 'graty',
            'db_password' => 'wrong-password',
        ]);

        $response->assertStatus(422)->assertJson(['ok' => false]);
    }

    public function test_store_requires_all_fields(): void
    {
        $this->post(route('install.store'), [])
            ->assertSessionHasErrors(['db_host', 'db_database', 'db_username', 'admin_name', 'admin_email', 'admin_password']);
    }

    public function test_store_rejects_an_admin_password_that_does_not_meet_complexity_requirements(): void
    {
        $response = $this->post(route('install.store'), $this->validPayload([
            'admin_password' => 'password',
            'admin_password_confirmation' => 'password',
        ]));

        $response->assertSessionHasErrors('admin_password');
        $this->assertFalse(User::query()->exists());
    }

    public function test_store_shows_error_when_database_connection_fails(): void
    {
        $response = $this->post(route('install.store'), $this->validPayload([
            'db_password' => 'wrong-password',
        ]));

        $response->assertRedirect();
        $this->assertTrue(session()->has('installError'));
        $this->assertFalse(User::query()->exists());
    }

    public function test_full_installation_wizard_completes_end_to_end(): void
    {
        $database = 'craty_installer_test';
        $this->createScratchDatabase($database);

        try {
            // root/root (patrz docker-compose.yml MARIADB_ROOT_PASSWORD) — user
            // "graty" ma uprawnienia tylko do bazy "graty", nie do świeżo
            // utworzonej bazy scratch, więc realny przebieg instalatora też
            // musiałby dostać dane z pełnymi prawami do docelowej bazy.
            $response = $this->post(route('install.store'), $this->validPayload([
                'db_username' => 'root',
                'db_password' => 'root',
                'db_database' => $database,
                'app_name' => 'Testowe Craty',
                'demo_data' => '1',
            ]));

            $response->assertRedirect(route('install.done'));

            // Od teraz appka mówi z bazą scratch, nie testową sqlite.
            $this->assertSame('mysql', config('database.default'));
            $this->assertSame('Testowe Craty', AppSetting::current()->name);

            $admin = User::where('email', 'admin@example.com')->first();
            $this->assertNotNull($admin);
            $this->assertSame('admin', $admin->role);
            $this->assertTrue($admin->protected);

            // Checkbox "dane przykładowe" zaznaczony -> DemoDataSeeder odpalony.
            $this->assertTrue(Item::query()->exists());

            $done = $this->get(route('install.done'));
            $done->assertOk()->assertSee('admin@example.com');
        } finally {
            $this->dropScratchDatabase($database);
        }
    }

    public function test_install_done_redirects_to_login_without_the_one_time_flag(): void
    {
        $this->get(route('install.done'))->assertRedirect(route('login'));
    }

    public function test_cleanup_redirects_to_login_without_the_one_time_flag(): void
    {
        $this->post(route('install.cleanup'))->assertRedirect(route('login'));
    }

    public function test_cleanup_removes_installer_files_when_flag_is_present(): void
    {
        // Patrz InstallerCleanupServiceTest — InstallerCleanupService dzieli
        // "app.update_root_path" z UpdateService, więc podmieniamy je tak
        // samo, żeby ta trasa nigdy nie dotknęła prawdziwych plików repo.
        $root = sys_get_temp_dir().'/craty-cleanup-controller-test-'.uniqid();
        mkdir($root.'/routes', 0755, true);
        mkdir($root.'/app/Http/Controllers', 0755, true);
        mkdir($root.'/resources/views/install', 0755, true);
        file_put_contents($root.'/routes/web.php', "<?php\nrequire __DIR__.'/install.php';\n");
        file_put_contents($root.'/routes/install.php', '<?php');
        file_put_contents($root.'/app/Http/Controllers/InstallController.php', '<?php');

        config(['app.update_root_path' => $root]);
        session(['justInstalled' => true, 'adminEmail' => 'admin@example.com']);

        $response = $this->post(route('install.cleanup'));

        $response->assertRedirect(route('login'));
        $this->assertFileDoesNotExist($root.'/routes/install.php');
        $this->assertFileDoesNotExist($root.'/app/Http/Controllers/InstallController.php');
        $this->assertDirectoryDoesNotExist($root.'/resources/views/install');
        $this->assertFalse(session()->has('justInstalled'));

        $this->deleteDirectory($root);
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

    /** @return array<string, mixed> */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'db_host' => 'db',
            'db_port' => 3306,
            'db_database' => 'graty',
            'db_username' => 'graty',
            'db_password' => 'graty',
            'app_name' => 'Craty',
            'admin_name' => 'Administrator',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'Passw0rd!123',
            'admin_password_confirmation' => 'Passw0rd!123',
        ], $overrides);
    }

    private function createScratchDatabase(string $name): void
    {
        // root/root, bo user "graty" ma uprawnienia tylko do bazy "graty"
        // (patrz docker-compose.yml) — do CREATE DATABASE trzeba czegoś szerszego.
        $pdo = new \PDO('mysql:host=db;port=3306', 'root', 'root');
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}`");
    }

    private function dropScratchDatabase(string $name): void
    {
        try {
            $pdo = new \PDO('mysql:host=db;port=3306', 'root', 'root');
            $pdo->exec("DROP DATABASE IF EXISTS `{$name}`");
        } catch (\Throwable) {
            // Sprzątanie best-effort — nie psujemy wyniku testu, jeśli się nie uda.
        }

        // Przywracamy domyślne połączenie appki na sqlite testowe, żeby kolejne
        // testy w tym samym procesie nie odziedziczyły podmienionego configu.
        config(['database.default' => 'sqlite']);
        DB::setDefaultConnection('sqlite');
        DB::purge('mysql');
    }
}
