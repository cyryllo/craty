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
     * Gość trafiający na "/" jest przekierowywany do logowania
     * (panel wymaga zalogowania — patrz routes/web.php).
     */
    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
