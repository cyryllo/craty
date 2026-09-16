<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_backup_index(): void
    {
        Storage::fake('backups');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('settings.backup.index'))
            ->assertOk()
            ->assertSee('No backups yet.');
    }

    public function test_magazynier_cannot_access_backups(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($magazynier)->get(route('settings.backup.index'))->assertForbidden();
        $this->actingAs($magazynier)->post(route('settings.backup.run'))->assertForbidden();
    }

    public function test_admin_can_trigger_a_backup_run(): void
    {
        Storage::fake('backups');
        Artisan::shouldReceive('call')
            ->once()
            ->with('backup:run', ['--disable-notifications' => true])
            ->andReturn(0);
        Artisan::shouldReceive('output')->andReturn('');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('settings.backup.run'))
            ->assertRedirect(route('settings.backup.index'))
            ->assertSessionHas('status', __('Backup created.'));
    }

    public function test_a_failed_backup_run_shows_the_real_reason_instead_of_a_false_success(): void
    {
        Storage::fake('backups');
        Artisan::shouldReceive('call')
            ->once()
            ->with('backup:run', ['--disable-notifications' => true])
            ->andReturn(1);
        Artisan::shouldReceive('output')->andReturn('Backup failed because disk "backups" does not exist.');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('settings.backup.run'))
            ->assertRedirect()
            ->assertSessionHas('error', __('Backup failed: :reason', [
                'reason' => 'Backup failed because disk "backups" does not exist.',
            ]));
    }

    public function test_admin_can_update_backup_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('settings.backup.settings'), [
            'backup_retention_days' => 30,
            'backup_include_env' => '1',
        ])->assertRedirect(route('settings.backup.index'));

        $setting = AppSetting::current();
        $this->assertSame(30, $setting->backup_retention_days);
        $this->assertTrue($setting->backup_include_env);
    }

    public function test_admin_can_download_an_existing_backup(): void
    {
        Storage::fake('backups');
        Storage::disk('backups')->put('craty/2026-09-14.zip', 'fake-zip-contents');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('settings.backup.download', 'craty/2026-09-14.zip'))
            ->assertOk();
    }

    public function test_downloading_unknown_backup_is_not_found(): void
    {
        Storage::fake('backups');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('settings.backup.download', 'craty/missing.zip'))
            ->assertNotFound();
    }

    public function test_backup_path_cannot_traverse_outside_the_disk(): void
    {
        Storage::fake('backups');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('settings.backup.download', '../../.env'))
            ->assertNotFound();
    }

    public function test_admin_can_delete_a_backup(): void
    {
        Storage::fake('backups');
        Storage::disk('backups')->put('craty/2026-09-14.zip', 'fake-zip-contents');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->delete(route('settings.backup.destroy', 'craty/2026-09-14.zip'))
            ->assertRedirect();

        Storage::disk('backups')->assertMissing('craty/2026-09-14.zip');
    }
}
