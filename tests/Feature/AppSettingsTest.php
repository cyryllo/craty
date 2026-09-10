<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AppSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_app_name_and_logo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('settings.app.update'), [
            'name' => 'Graty Sp. z o.o.',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ]);

        $response->assertRedirect();
        $setting = AppSetting::current();
        $this->assertSame('Graty Sp. z o.o.', $setting->name);
        Storage::disk('public')->assertExists($setting->logo_path);

        $this->actingAs($admin)->get('/dashboard')->assertSee('Graty Sp. z o.o.');
    }

    public function test_magazynier_cannot_access_app_settings(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($magazynier)->get(route('settings.app.edit'))->assertForbidden();
        $this->actingAs($magazynier)->post(route('settings.app.update'), ['name' => 'X'])->assertForbidden();
    }

    public function test_settings_hub_shows_only_role_appropriate_cards(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($magazynier)->get(route('settings.index'))
            ->assertSee('Categories')
            ->assertDontSee('Users')
            ->assertDontSee('App settings');

        $this->actingAs($admin)->get(route('settings.index'))
            ->assertSee('Categories')
            ->assertSee('Users')
            ->assertSee('App settings');
    }

    public function test_viewer_cannot_reach_settings_hub(): void
    {
        $viewer = User::factory()->create(['role' => 'podglad']);

        $this->actingAs($viewer)->get(route('settings.index'))->assertForbidden();
    }
}
