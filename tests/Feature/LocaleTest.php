<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_defaults_to_english(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->get('/items')->assertSee('Items')->assertDontSee('Przedmioty');
    }

    public function test_user_can_switch_their_own_language(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->post(route('locale.update'), ['locale' => 'pl'])->assertRedirect();
        $this->assertSame('pl', $user->refresh()->locale);

        $this->actingAs($user)->get('/items')->assertSee('Przedmioty');
    }

    public function test_admin_sets_global_default_language_for_users_without_a_preference(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $viewer = User::factory()->create(['role' => 'podglad']);

        $this->actingAs($admin)->post(route('settings.app.update'), ['locale' => 'pl'])->assertRedirect();
        $this->assertSame('pl', AppSetting::current()->locale);

        // Viewer nie ustawił własnego języka, więc dostaje globalny domyślny.
        $this->actingAs($viewer)->get('/items')->assertSee('Przedmioty');

        // Ale osobista preferencja użytkownika i tak wygrywa z globalnym ustawieniem.
        $viewer->update(['locale' => 'en']);
        $this->actingAs($viewer)->get('/items')->assertSee('Items');
    }
}
