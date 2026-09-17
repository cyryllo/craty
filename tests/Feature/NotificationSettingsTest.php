<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_notification_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('settings.notifications.edit'))->assertOk();
    }

    public function test_magazynier_cannot_access_notification_settings(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($magazynier)->get(route('settings.notifications.edit'))->assertForbidden();
        $this->actingAs($magazynier)->post(route('settings.notifications.update'), [])->assertForbidden();
    }

    public function test_password_reset_is_enabled_by_default(): void
    {
        $this->assertTrue(AppSetting::current()->password_reset_enabled);
        $this->get('/forgot-password')->assertOk();
        $this->get('/login')->assertSee(route('password.request'), false);
    }

    public function test_loan_due_notifications_are_disabled_by_default(): void
    {
        $this->assertFalse(AppSetting::current()->loan_due_notifications_enabled);
    }

    public function test_admin_can_disable_password_reset(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('settings.notifications.update'), [
            'password_reset_enabled' => '0',
            'loan_due_notifications_enabled' => '0',
        ]);

        $response->assertRedirect();
        $this->assertFalse(AppSetting::current()->password_reset_enabled);
    }

    public function test_disabling_password_reset_hides_the_forgot_password_routes_and_link(): void
    {
        AppSetting::current()->fill(['password_reset_enabled' => false])->save();
        $user = User::factory()->create();

        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', ['email' => $user->email])->assertNotFound();
        $this->get('/reset-password/some-token')->assertNotFound();
        $this->post('/reset-password', [])->assertNotFound();
        $this->get('/login')->assertDontSee(route('password.request'), false);
    }

    public function test_admin_can_enable_loan_due_notifications(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('settings.notifications.update'), [
            'password_reset_enabled' => '1',
            'loan_due_notifications_enabled' => '1',
        ]);

        $response->assertRedirect();
        $this->assertTrue(AppSetting::current()->loan_due_notifications_enabled);
    }
}
