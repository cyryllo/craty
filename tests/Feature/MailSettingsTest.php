<?php

namespace Tests\Feature;

use App\Mail\TestMail;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_mail_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('settings.mail.edit'))->assertOk();
    }

    public function test_magazynier_cannot_access_mail_settings(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($magazynier)->get(route('settings.mail.edit'))->assertForbidden();
        $this->actingAs($magazynier)->post(route('settings.mail.update'), [])->assertForbidden();
    }

    public function test_admin_can_save_mail_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('settings.mail.update'), [
            'mail_host' => 'smtp.example.com',
            'mail_port' => 587,
            'mail_encryption' => 'tls',
            'mail_username' => 'bot@example.com',
            'mail_password' => 'secret123',
            'mail_from_address' => 'noreply@example.com',
            'mail_from_name' => 'Craty',
        ]);

        $response->assertRedirect(route('settings.mail.edit'));

        $setting = AppSetting::current();
        $this->assertSame('smtp.example.com', $setting->mail_host);
        $this->assertSame(587, $setting->mail_port);
        $this->assertSame('secret123', $setting->mail_password);
    }

    public function test_blank_password_keeps_existing_password_on_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        AppSetting::current()->fill([
            'mail_host' => 'smtp.example.com',
            'mail_port' => 587,
            'mail_password' => 'original-secret',
            'mail_from_address' => 'noreply@example.com',
            'mail_from_name' => 'Craty',
        ])->save();

        $this->actingAs($admin)->post(route('settings.mail.update'), [
            'mail_host' => 'smtp.example.com',
            'mail_port' => 587,
            'mail_encryption' => 'tls',
            'mail_from_address' => 'noreply@example.com',
            'mail_from_name' => 'Craty',
            'mail_password' => '',
        ])->assertRedirect();

        $this->assertSame('original-secret', AppSetting::current()->mail_password);
    }

    public function test_admin_can_send_test_email(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('settings.mail.test'), [
            'mail_host' => 'smtp.example.com',
            'mail_port' => 587,
            'mail_encryption' => 'tls',
            'mail_username' => '',
            'mail_password' => 'secret',
            'mail_from_address' => 'noreply@example.com',
            'mail_from_name' => 'Craty',
            'test_email' => 'someone@example.com',
        ]);

        $response->assertRedirect();
        Mail::assertSent(TestMail::class);
    }
}
