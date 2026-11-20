<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use App\Support\PwaIcons;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Ikona aplikacji (PWA) z własnego logo, po zaznaczeniu opcji w Ustawieniach. */
class AppIconFromLogoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function save(array $data): void
    {
        $this->actingAs($this->admin)->post(route('settings.app.update'), $data)->assertSessionHasNoErrors();
    }

    public function test_logo_becomes_the_app_icon_when_the_option_is_on(): void
    {
        $this->save(['logo' => UploadedFile::fake()->image('logo.png', 300, 120), 'logo_as_app_icon' => 1]);

        $icons = collect($this->get('/manifest.webmanifest')->json('icons'));
        foreach ([192, 512] as $size) {
            $src = $icons->firstWhere('sizes', "{$size}x{$size}")['src'];
            $this->assertStringStartsWith('/storage/branding/app-icon-', $src);

            $file = Storage::disk('public')->path(substr($src, strlen('/storage/')));
            [$width, $height] = getimagesize($file);
            $this->assertSame([$size, $size], [$width, $height], 'Ikona jest kwadratowa, nawet z szerokiego logo.');
        }
        $this->assertSame(PwaIcons::url(192), $icons->firstWhere('sizes', '192x192')['src']);
    }

    public function test_without_the_option_the_default_icon_stays(): void
    {
        $this->save(['logo' => UploadedFile::fake()->image('logo.png', 200, 200)]);

        $this->assertSame('/pwa-icons/icon-192.png?v='.PwaIcons::VERSION, PwaIcons::url(192));
        $this->assertSame([], collect(Storage::disk('public')->files('branding'))->filter(fn ($f) => str_contains($f, 'app-icon-'))->values()->all());
    }

    public function test_turning_the_option_off_or_changing_the_logo_removes_old_icons(): void
    {
        $this->save(['logo' => UploadedFile::fake()->image('a.png', 200, 200), 'logo_as_app_icon' => 1]);
        $first = PwaIcons::url(192);

        $this->save(['logo' => UploadedFile::fake()->image('b.png', 200, 200), 'logo_as_app_icon' => 1]);
        $second = PwaIcons::url(192);
        $this->assertNotSame($first, $second, 'Nowe logo = nowy adres ikony, telefony pobiorą nową.');

        $this->save(['logo_as_app_icon' => 0]);
        $this->assertSame([], collect(Storage::disk('public')->files('branding'))->filter(fn ($f) => str_contains($f, 'app-icon-'))->values()->all());
        $this->assertStringStartsWith('/pwa-icons/', PwaIcons::url(192));
    }
}
