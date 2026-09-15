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

    /**
     * Regresja: @if(...) required @endif (i tak samo @required(...)) wewnątrz
     * tagu komponentu <x-text-input> psuło parser tagów Blade — cały tag
     * lądował w HTML-u jako dosłowny, nieprzetworzony tekst zamiast <input>,
     * więc pole hasła znikało z formularza (zgłoszone przez użytkownika).
     * Poprawka rozdziela to na dwa czyste warianty tagu w @if/@else.
     */
    public function test_create_form_renders_a_real_required_password_input(): void
    {
        $admin = $this->protectedAdmin();

        $response = $this->actingAs($admin)->get(route('users.create'));

        $response->assertOk();
        $response->assertDontSee('<x-text-input', false);
        $response->assertSee('type="password"', false);
        $response->assertSee('required', false);
    }

    public function test_edit_form_renders_a_real_optional_password_input(): void
    {
        $admin = $this->protectedAdmin();
        $other = User::factory()->create(['role' => 'magazynier']);

        $response = $this->actingAs($admin)->get(route('users.edit', $other));

        $response->assertOk();
        $response->assertDontSee('<x-text-input', false);
        $response->assertSee('type="password"', false);
    }

    public function test_admin_can_create_a_user_with_a_password(): void
    {
        $admin = $this->protectedAdmin();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Nowy Magazynier',
            'email' => 'nowy@craty.test',
            'role' => 'magazynier',
            'password' => 'bardzo-tajne-haslo',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertTrue(User::where('email', 'nowy@craty.test')->exists());
    }
}
