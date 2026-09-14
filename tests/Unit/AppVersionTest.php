<?php

namespace Tests\Unit;

use App\Support\AppVersion;
use PHPUnit\Framework\TestCase;

class AppVersionTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = tempnam(sys_get_temp_dir(), 'craty-version-test-');
    }

    protected function tearDown(): void
    {
        // file_exists(), nie tylko @unlink() — PHPUnit 11 zgłasza jako
        // "warning" nawet stłumiony błąd unlink() na już nieistniejącym
        // pliku (patrz test niżej, który sam usuwa plik w środku testu).
        if (file_exists($this->path)) {
            unlink($this->path);
        }

        parent::tearDown();
    }

    public function test_current_returns_a_fallback_when_the_file_is_missing(): void
    {
        unlink($this->path);

        $this->assertSame('0.0.0', (new AppVersion($this->path))->current());
    }

    public function test_current_reads_the_trimmed_file_contents(): void
    {
        file_put_contents($this->path, "1.4.2\n");

        $this->assertSame('1.4.2', (new AppVersion($this->path))->current());
    }

    public function test_set_writes_the_version_with_a_trailing_newline(): void
    {
        $version = new AppVersion($this->path);
        $version->set('2.0.0');

        $this->assertSame("2.0.0\n", file_get_contents($this->path));
        $this->assertSame('2.0.0', $version->current());
    }
}
