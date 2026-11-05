<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Obraz Docker (docker/release/Dockerfile, CRATY_DOCKER=true) ma bazę w tym
 * samym kontenerze: instalator nie pyta o nią i nigdy nie bierze jej danych
 * z formularza, a aktualizacja to nowy obraz, nie paczka .zip.
 */
class DockerModeTest extends TestCase
{
    use RefreshDatabase;

    protected bool $withoutDefaultInstalledUser = true;

    private string $tempEnvPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempEnvPath = tempnam(sys_get_temp_dir(), 'craty-docker-test-env-');
        config([
            'app.env_file_path' => $this->tempEnvPath,
            'app.docker' => true,
            'database.connections.mysql.host' => 'craty-bundled-db.invalid',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->tempEnvPath);
        parent::tearDown();
    }

    public function test_installer_skips_the_database_form(): void
    {
        $this->get(route('install.show'))
            ->assertOk()
            ->assertSee('The database is built into this Docker image')
            ->assertDontSee('name="db_host"', false)
            ->assertDontSee('name="db_password"', false);
    }

    public function test_installer_uses_the_bundled_database_and_ignores_values_from_the_form(): void
    {
        $response = $this->post(route('install.store'), [
            'db_host' => 'evil.example', 'db_port' => 3306, 'db_database' => 'x', 'db_username' => 'x', 'db_password' => 'x',
            'app_url' => 'http://craty.test',
            'admin_name' => 'Administrator', 'admin_email' => 'admin@example.com',
            'admin_password' => 'Passw0rd!123', 'admin_password_confirmation' => 'Passw0rd!123',
        ]);

        // Bez pól bazy w formularzu walidacja przechodzi, a połączenie idzie
        // pod adres z konfiguracji (tu celowo nieistniejący), nie z formularza.
        $response->assertSessionHasNoErrors();
        $this->assertStringContainsString('craty-bundled-db.invalid', session('installError'));
        $this->assertStringNotContainsString('evil.example', session('installError'));
    }

    public function test_updates_page_explains_docker_updates_and_upload_is_disabled(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Session::put('auth.password_confirmed_at', time());

        $this->actingAs($admin)->get(route('settings.updates.index'))
            ->assertOk()
            ->assertSee('docker compose pull')
            ->assertDontSee('name="package"', false);

        $this->actingAs($admin)->post(route('settings.updates.upload'))->assertNotFound();
    }
}
