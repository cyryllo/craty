<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tryb ciemny (TODO.md "Wygoda dnia codziennego") — Tailwind darkMode:
 * 'class' (patrz tailwind.config.js), przełączany ręcznie przyciskiem
 * (layouts/_theme-toggle.blade.php), zapamiętywany w localStorage per
 * przeglądarkę (layouts/_theme-head.blade.php), nie w bazie. PHPUnit nie
 * renderuje CSS/kolorów naprawdę — te testy sprawdzają tylko, że sam
 * mechanizm (skrypt anti-flash + przycisk) faktycznie trafia na każdą
 * niezależną stronę HTML appki, nie że kolory wyglądają dobrze.
 */
class DarkModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_includes_the_theme_toggle_and_anti_flash_script(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('toggleTheme()', false)
            ->assertSee("localStorage.getItem('theme')", false);
    }

    public function test_login_page_includes_the_theme_toggle_and_anti_flash_script(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('toggleTheme()', false)
            ->assertSee("localStorage.getItem('theme')", false);
    }

    // Test dla /install jest w InstallerTest — ta klasa potrzebuje
    // $withoutDefaultInstalledUser = true zadeklarowane na poziomie klasy,
    // bo TestCase::setUp() czyta tę flagę zanim ciało testu w ogóle się
    // wykona (za późno na ustawienie jej tutaj).

    public function test_flea_market_page_includes_the_theme_toggle_and_anti_flash_script(): void
    {
        AppSetting::current()->fill(['public_marketplace_enabled' => true])->save();

        $this->get(route('marketplace.index'))
            ->assertOk()
            ->assertSee('toggleTheme()', false)
            ->assertSee("localStorage.getItem('theme')", false);
    }

    /** Tailwind musi wiedzieć, że motyw przełącza klasa na <html>, nie prefers-color-scheme — inaczej przycisk nic by nie robił. */
    public function test_tailwind_config_uses_class_based_dark_mode(): void
    {
        $config = file_get_contents(base_path('tailwind.config.js'));

        $this->assertStringContainsString("darkMode: 'class'", $config);
    }

    /**
     * Regresja: <body> w layouts/app (w przeciwieństwie do guest/install/
     * pchli targ, które miały text-gray-900 od początku) nie miało żadnego
     * jawnego koloru tekstu, więc KAŻDY element bez własnej klasy text-*
     * (np. przyciski "Drukuj etykietę"/"Edytuj" na items/show.blade.php)
     * dziedziczył domyślny czarny kolor przeglądarki — czarny tekst na
     * ciemnym tle, zgłoszone przez użytkownika ze screenshotem.
     */
    public function test_app_layout_body_sets_an_ambient_dark_text_color(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<body class="font-sans antialiased text-gray-900 dark:text-gray-100">', false);
    }
}
