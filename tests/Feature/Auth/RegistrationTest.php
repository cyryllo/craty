<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class RegistrationTest extends TestCase
{
    /**
     * Samodzielna rejestracja jest celowo wyłączona — konta zakłada
     * administrator przez /users (patrz routes/auth.php).
     */
    public function test_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }
}
