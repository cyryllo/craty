<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;
use ZipArchive;

class UpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $appRoot;

    protected function setUp(): void
    {
        parent::setUp();

        // Patrz UpdateServiceTest — appRoot/version_file_path muszą wskazywać
        // na katalog tymczasowy, żeby ten test nigdy nie dotknął tego repo.
        $this->appRoot = sys_get_temp_dir().'/craty-update-controller-test-'.uniqid();
        mkdir($this->appRoot.'/app', 0755, true);
        mkdir($this->appRoot.'/storage/app/public', 0755, true);
        file_put_contents($this->appRoot.'/VERSION', "1.0.0\n");
        file_put_contents($this->appRoot.'/.env', "APP_KEY=nie-ruszac\n");

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

    public function test_admin_can_view_the_updates_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('settings.updates.index'))
            ->assertOk()
            ->assertSee('1.0.0');
    }

    /**
     * Kontener testowy ma hojne php.ini (128M/128M, patrz PhpUploadLimitsTest
     * dla samej logiki progu) — na tej maszynie baner ostrzegawczy z
     * TODO.md "Drobne rzeczy zauważone przy budowie" nie ma się prawa
     * pokazać. Symulowanie faktycznie niskiego limitu wymagałoby zmiany
     * php.ini na sztywno w kontenerze (upload_max_filesize/post_max_size to
     * dyrektywy PHP_INI_PERDIR — ini_set() w locie ich nie zmienia), więc
     * sam próg jest przetestowany jednostkowo w PhpUploadLimitsTest.
     */
    public function test_updates_page_does_not_warn_about_upload_limits_on_this_dev_environment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('settings.updates.index'))
            ->assertOk()
            ->assertDontSee('upload_max_filesize');
    }

    public function test_magazynier_cannot_access_updates(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($magazynier)->get(route('settings.updates.index'))->assertForbidden();
    }

    public function test_upload_requires_a_recently_confirmed_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Bez potwierdzonego hasła w sesji — password.confirm ma przekierować
        // do ekranu potwierdzenia zamiast wpuścić do upload().
        $response = $this->actingAs($admin)->post(route('settings.updates.upload'), [
            'package' => $this->fakeZip(['version' => '1.1.0']),
        ]);

        $response->assertRedirect(route('password.confirm'));
    }

    public function test_admin_can_apply_a_valid_update_package(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->confirmPassword();

        $response = $this->actingAs($admin)->post(route('settings.updates.upload'), [
            'package' => $this->fakeZip(['version' => '1.1.0']),
        ]);

        $response->assertRedirect(route('settings.updates.index'));
        $this->assertSame("1.1.0\n", file_get_contents($this->appRoot.'/VERSION'));
    }

    /**
     * PHP czyści $_POST/$_FILES bez wyjątku gdy body przekroczy
     * post_max_size (patrz PhpUploadLimits::requestWasTruncated()) — jedyny
     * ślad to Content-Length > 0 przy obu pustych paczkach danych.
     * Odtwarzamy dokładnie ten sygnał ręcznie przez call(), bo Laravelowy
     * klient testowy nie przechodzi przez prawdziwe wymuszanie limitów PHP.
     */
    public function test_upload_shows_a_friendly_error_when_php_silently_truncated_an_oversized_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->confirmPassword();

        $response = $this->actingAs($admin)->call('POST', route('settings.updates.upload'), [], [], [], [
            'CONTENT_LENGTH' => '99999999',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('upload_max_filesize', session('updateError'));
        $this->assertSame("1.0.0\n", file_get_contents($this->appRoot.'/VERSION'));
    }

    public function test_upload_shows_an_error_for_an_older_version(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->confirmPassword();

        $response = $this->actingAs($admin)->post(route('settings.updates.upload'), [
            'package' => $this->fakeZip(['version' => '0.9.0']),
        ]);

        $response->assertRedirect();
        $this->assertTrue(session()->has('updateError'));
        $this->assertSame("1.0.0\n", file_get_contents($this->appRoot.'/VERSION'));
    }

    private function confirmPassword(): void
    {
        Session::put('auth.password_confirmed_at', time());
    }

    private function fakeZip(array $manifest): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'craty-upload-').'.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('update-manifest.json', json_encode($manifest));
        $zip->addFromString('app/placeholder.php', '<?php // nowy kod');
        $zip->close();

        return new UploadedFile($path, 'update.zip', 'application/zip', null, true);
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
