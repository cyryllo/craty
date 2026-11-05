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
        $this->assertSame('/dashboard', $manifest['id']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertFileExists(public_path('pwa-icons/icon-192.png'));
        $this->assertFileExists(public_path('pwa-icons/icon-512.png'));

        // Osobne wpisy "any" i "maskable" (łączone "any maskable" Chrome odradza)
        // i adresy z tą samą wersją co w widokach, żeby telefony pobrały nowe ikony.
        $icons = collect($manifest['icons']);
        $this->assertSame(['any', 'maskable'], $icons->pluck('purpose')->unique()->sort()->values()->all());
        foreach ([192, 512] as $size) {
            $this->assertSame(2, $icons->where('src', \App\Support\PwaIcons::url($size))->count(), "Ikona {$size}: any + maskable z aktualną wersją");
        }
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
