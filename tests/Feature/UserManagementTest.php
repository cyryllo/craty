<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function protectedAdmin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->forceFill(['protected' => true])->save();

        return $admin;
    }

    public function test_protected_admin_cannot_be_deleted(): void
    {
        $mainAdmin = $this->protectedAdmin();
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($otherAdmin)->delete(route('users.destroy', $mainAdmin));

        $response->assertRedirect();
        $this->assertModelExists($mainAdmin);
    }

    public function test_protected_admin_role_and_active_state_cannot_be_changed(): void
    {
        $mainAdmin = $this->protectedAdmin();
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($otherAdmin)->put(route('users.update', $mainAdmin), [
            'name' => $mainAdmin->name,
            'email' => $mainAdmin->email,
            'role' => 'podglad',
            // 'active' celowo pominięte — symuluje odznaczenie checkboxa.
        ]);

        $mainAdmin->refresh();
        $this->assertSame('admin', $mainAdmin->role);
        $this->assertTrue($mainAdmin->active);
    }

    public function test_non_protected_admin_can_still_be_deleted(): void
    {
        $mainAdmin = $this->protectedAdmin();
        $regularAdmin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($mainAdmin)->delete(route('users.destroy', $regularAdmin))->assertRedirect();

        $this->assertModelMissing($regularAdmin);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $mainAdmin = $this->protectedAdmin();
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($otherAdmin)->delete(route('users.destroy', $otherAdmin))->assertRedirect();

        $this->assertModelExists($otherAdmin);
    }
}
