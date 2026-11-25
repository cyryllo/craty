<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\Item;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/** Kopie zapasowe z panelu: tworzenie, lista, pobieranie, automat, wgrywanie, przywracanie. */
class BackupTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/craty-backup-test-'.bin2hex(random_bytes(4));
        config(['app.backup_path' => $this->dir]);
        Storage::fake('public');
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Ewa']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function item(string $name): Item
    {
        static $no = 0;

        return Item::create(['inventory_no' => 'B-'.++$no, 'name' => $name, 'condition' => 'uzywany', 'status' => 'dostepny']);
    }

    private function confirmed(): static
    {
        return $this->actingAs($this->admin)->withSession(['auth.password_confirmed_at' => time()]);
    }

    private function zipEntries(string $name): array
    {
        $zip = new ZipArchive;
        $zip->open($this->dir.'/'.$name);
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[] = $zip->getNameIndex($i);
        }
        $sql = $zip->getFromName('database.sql');
        $zip->close();

        return [$entries, $sql];
    }

    public function test_backup_contains_database_files_and_instructions(): void
    {
        $this->item("Wiertarka O'Brien\nz walizką");
        Storage::disk('public')->put('items/1/photo.jpg', 'jpeg-bytes');

        $this->actingAs($this->admin)->post(route('settings.backups.store'))->assertSessionHasNoErrors();

        $backups = app(BackupService::class)->list();
        $this->assertCount(1, $backups);
        $this->assertSame('manual', $backups[0]['type']);
        [$entries, $sql] = $this->zipEntries($backups[0]['name']);
        $this->assertContains('manifest.json', $entries);
        $this->assertContains('CZYTAJ-MNIE.txt', $entries);
        $this->assertContains('files/items/1/photo.jpg', $entries);
        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringContainsString("O''Brien", $sql);
        $this->assertStringContainsString(BackupService::STATEMENT_END, $sql);
        $this->assertSame(1, ActivityLog::where('event', 'backup.created')->count());
    }

    public function test_download_needs_a_confirmed_password_and_a_valid_name(): void
    {
        $name = app(BackupService::class)->create();

        $this->actingAs($this->admin)->get(route('settings.backups.download', $name))->assertRedirect(route('password.confirm'));
        $this->confirmed()->get(route('settings.backups.download', $name))->assertOk()->assertDownload($name);
        $this->confirmed()->get(route('settings.backups.download', '..%2F..%2F.env'))->assertNotFound();
        $this->confirmed()->get(route('settings.backups.download', 'craty-kopia-2020-01-01-000000.zip'))->assertNotFound();

        $this->actingAs(User::factory()->create(['role' => 'magazynier']))->get(route('settings.backups.index'))->assertForbidden();
    }

    public function test_old_backups_are_pruned_but_the_newest_stays(): void
    {
        AppSetting::current()->fill(['backup_retention_days' => 7])->save();
        foreach (['2026-01-01-100000', '2026-01-02-100000'] as $stamp) {
            $file = $this->dir.'/craty-kopia-'.$stamp.'.zip';
            File::ensureDirectoryExists($this->dir);
            File::put($file, 'x');
            touch($file, now()->subDays(30)->getTimestamp());
        }

        $this->assertSame(1, app(BackupService::class)->prune());
        $this->assertCount(1, app(BackupService::class)->list());
    }

    public function test_automatic_backups_run_only_when_switched_on_and_due(): void
    {
        $this->artisan('backup:run', ['--auto' => true])->assertSuccessful();
        $this->assertCount(0, app(BackupService::class)->list());

        $this->actingAs($this->admin)->post(route('settings.backups.settings'), ['backup_schedule' => 'weekly', 'backup_retention_days' => 30])->assertSessionHasNoErrors();
        $this->artisan('backup:run', ['--auto' => true])->assertSuccessful();
        $this->artisan('backup:run', ['--auto' => true])->assertSuccessful(); // tydzień jeszcze nie minął
        $this->assertSame(['auto'], array_column(app(BackupService::class)->list(), 'type'));

        $this->travel(7)->days();
        $this->artisan('backup:run', ['--auto' => true])->assertSuccessful();
        $this->assertCount(2, app(BackupService::class)->list());
    }

    public function test_upload_accepts_only_craty_backups(): void
    {
        $name = app(BackupService::class)->create();
        $copy = sys_get_temp_dir().'/kopia-z-innego-serwera.zip';
        File::copy($this->dir.'/'.$name, $copy);

        $this->confirmed()->post(route('settings.backups.upload'), ['backup' => new UploadedFile($copy, 'kopia.zip', 'application/zip', null, true)])
            ->assertSessionHasNoErrors();
        $this->assertContains('uploaded', array_column(app(BackupService::class)->list(), 'type'));

        $fake = sys_get_temp_dir().'/nie-kopia.zip';
        $zip = new ZipArchive;
        $zip->open($fake, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('evil.php', '<?php echo 1;');
        $zip->close();
        $this->confirmed()->post(route('settings.backups.upload'), ['backup' => new UploadedFile($fake, 'nie-kopia.zip', 'application/zip', null, true)])
            ->assertSessionHas('error', __('This is not a Craty backup.'));
        $this->assertCount(2, app(BackupService::class)->list());
    }

    public function test_restore_brings_back_data_and_files_after_a_safety_backup(): void
    {
        $this->item('Wiertarka');
        Storage::disk('public')->put('items/1/photo.jpg', 'stare-zdjecie');
        $name = app(BackupService::class)->create();

        Item::where('name', 'Wiertarka')->delete();
        $this->item('Szlifierka');
        Storage::disk('public')->delete('items/1/photo.jpg');
        Storage::disk('public')->put('items/2/nowe.jpg', 'nowe');

        $this->actingAs($this->admin)->post(route('settings.backups.restore', $name))->assertRedirect(route('password.confirm'));
        $this->confirmed()->post(route('settings.backups.restore', $name))->assertRedirect(route('login'));

        $this->assertSame(['Wiertarka'], Item::pluck('name')->all());
        Storage::disk('public')->assertExists('items/1/photo.jpg');
        Storage::disk('public')->assertMissing('items/2/nowe.jpg');
        $this->assertGuest();
        $this->assertContains('pre-restore', array_column(app(BackupService::class)->list(), 'type'));
        $this->assertSame(1, ActivityLog::where('event', 'backup.restored')->count());
    }
}
