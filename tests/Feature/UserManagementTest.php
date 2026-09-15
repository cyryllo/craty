<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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
            'password' => 'Bardzo-Tajne-1',
            'password_confirmation' => 'Bardzo-Tajne-1',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertTrue(User::where('email', 'nowy@craty.test')->exists());
    }

    public function test_store_requires_password_confirmation_to_match(): void
    {
        $admin = $this->protectedAdmin();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Nowy Magazynier',
            'email' => 'nowy@craty.test',
            'role' => 'magazynier',
            'password' => 'Bardzo-Tajne-1',
            'password_confirmation' => 'Coś-Innego-2',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertFalse(User::where('email', 'nowy@craty.test')->exists());
    }

    #[DataProvider('weakPasswords')]
    public function test_store_rejects_a_password_that_does_not_meet_complexity_requirements(string $weak): void
    {
        $admin = $this->protectedAdmin();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Nowy Magazynier',
            'email' => 'nowy@craty.test',
            'role' => 'magazynier',
            'password' => $weak,
            'password_confirmation' => $weak,
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertFalse(User::where('email', 'nowy@craty.test')->exists());
    }

    public static function weakPasswords(): array
    {
        return [
            'too short' => ['Ab1!'],
            'no uppercase' => ['bardzo-tajne-1!'],
            'no lowercase' => ['BARDZO-TAJNE-1!'],
            'no symbol' => ['BardzoTajne123'],
        ];
    }

    public function test_update_with_a_new_password_still_enforces_complexity_and_confirmation(): void
    {
        $admin = $this->protectedAdmin();
        $other = User::factory()->create(['role' => 'magazynier']);

        $response = $this->actingAs($admin)->put(route('users.update', $other), [
            'name' => $other->name,
            'email' => $other->email,
            'role' => 'magazynier',
            'active' => '1',
            'password' => 'słabe',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_update_without_a_password_leaves_the_existing_one_unchanged(): void
    {
        $admin = $this->protectedAdmin();
        $other = User::factory()->create(['role' => 'magazynier']);
        $originalHash = $other->password;

        $response = $this->actingAs($admin)->put(route('users.update', $other), [
            'name' => $other->name,
            'email' => $other->email,
            'role' => 'magazynier',
            'active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertSame($originalHash, $other->refresh()->password);
    }
}
