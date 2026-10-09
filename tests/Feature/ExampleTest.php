<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    // RedirectToInstallerIfNotInstalled odsyła każde żądanie na /install bez
    // zainstalowanej appki — RefreshDatabase (+ domyślny user z Tests\TestCase)
    // odzwierciedla to, co i tak zakłada każdy inny test w tym pakiecie.
    use RefreshDatabase;

    /**
     * "/" to publiczna strona główna (pchli targ) z kłódką do logowania;
     * sam panel nadal wymaga zalogowania.
     */
    public function test_home_is_public_with_a_login_link_and_dashboard_requires_login(): void
    {
        $this->get('/')->assertOk()->assertSee('href="/login"', false);
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
