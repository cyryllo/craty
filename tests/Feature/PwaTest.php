<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_json_is_valid_and_declares_both_icon_sizes(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true);

        $this->assertSame('Craty', $manifest['name']);
        $this->assertSame('/dashboard', $manifest['start_url']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertCount(2, $manifest['icons']);
        $this->assertFileExists(public_path('pwa-icons/icon-192.png'));
        $this->assertFileExists(public_path('pwa-icons/icon-512.png'));
    }

    public function test_service_worker_file_exists_and_registers_a_fetch_handler(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertStringContainsString("addEventListener('fetch'", file_get_contents(public_path('sw.js')));
    }

    public function test_dashboard_links_the_manifest_and_registers_the_service_worker(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertSee('rel="manifest" href="/manifest.json"', false)
            ->assertSee("serviceWorker.register('/sw.js')", false);
    }

    public function test_login_page_also_links_the_manifest(): void
    {
        $this->get(route('login'))->assertSee('rel="manifest" href="/manifest.json"', false);
    }
}
