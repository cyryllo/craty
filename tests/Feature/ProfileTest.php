<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    /**
     * Regresja (zgłoszona przez użytkownika: "powiadomienia typu
     * profile-updated powinny być w danym języku a widzę że nie są") —
     * "profile-updated" to surowa, nieprzetłumaczona flaga wewnętrzna
     * Breeze, sprawdzana przez formularz profilu przez === (nie tekst do
     * wyświetlenia), która wcześniej wyciekała wprost na ekran przez
     * globalny baner statusu w layouts/app.blade.php (echo session('status')
     * bez __()). Zamiast niej ma się pokazać wyłącznie przetłumaczone
     * potwierdzenie z samego formularza.
     */
    public function test_the_raw_profile_updated_status_flag_is_never_shown_verbatim_on_screen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ])->assertSessionHas('status', 'profile-updated');

        // Sesja przenosi flash między żądaniami w tym samym teście —
        // sprawdzamy faktycznie wyrenderowaną stronę, nie samą flashowaną wartość.
        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertDontSee('profile-updated')
            ->assertSee(__('Saved.'));
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    /**
     * Konta (w tym ich usuwanie) zarządza wyłącznie admin przez /users —
     * samodzielne usunięcie własnego konta z Profilu nie istnieje.
     */
    public function test_users_cannot_delete_their_own_account_from_the_profile_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/profile');

        $response->assertStatus(405);
        $this->assertNotNull($user->fresh());
    }
}
