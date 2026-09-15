<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectToInstallerIfNotInstalledTest extends TestCase
{
    use RefreshDatabase;

    // Ten test celowo sprawdza stan "przed instalacją" — patrz Tests\TestCase.
    protected bool $withoutDefaultInstalledUser = true;

    public function test_any_page_redirects_to_the_installer_when_not_installed(): void
    {
        $this->get('/')->assertRedirect(route('install.show'));
        $this->get('/login')->assertRedirect(route('install.show'));
        $this->get('/dashboard')->assertRedirect(route('install.show'));
    }

    public function test_the_installer_itself_is_not_redirected(): void
    {
        $this->get(route('install.show'))->assertOk();
    }

    public function test_the_health_check_route_is_not_redirected(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_normal_pages_work_once_installed(): void
    {
        User::factory()->create(['role' => 'admin']);

        $this->get('/login')->assertOk();
    }
}
