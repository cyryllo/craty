<?php

namespace Tests\Unit;

use App\Support\EnvFileWriter;
use PHPUnit\Framework\TestCase;

class EnvFileWriterTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = tempnam(sys_get_temp_dir(), 'env-writer-test-');
        unlink($this->path); // testujemy też przypadek, gdy pliku jeszcze nie ma
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
        parent::tearDown();
    }

    public function test_ensure_exists_copies_from_example_when_missing(): void
    {
        $example = tempnam(sys_get_temp_dir(), 'env-example-');
        file_put_contents($example, "APP_NAME=Laravel\nAPP_KEY=\n");

        $writer = new EnvFileWriter($this->path);
        $writer->ensureExists($example);

        $this->assertFileExists($this->path);
        $this->assertSame('Laravel', $writer->get('APP_NAME'));
        unlink($example);
    }

    public function test_ensure_exists_does_nothing_when_file_already_present(): void
    {
        file_put_contents($this->path, "APP_NAME=Existing\n");
        $example = tempnam(sys_get_temp_dir(), 'env-example-');
        file_put_contents($example, "APP_NAME=FromExample\n");

        (new EnvFileWriter($this->path))->ensureExists($example);

        $this->assertSame('Existing', (new EnvFileWriter($this->path))->get('APP_NAME'));
        unlink($example);
    }

    public function test_set_replaces_an_existing_key_without_touching_others(): void
    {
        file_put_contents($this->path, "APP_NAME=Laravel\nAPP_KEY=\nDB_CONNECTION=sqlite\n");

        $writer = new EnvFileWriter($this->path);
        $writer->set(['APP_KEY' => 'base64:abc123']);

        $this->assertSame('Laravel', $writer->get('APP_NAME'));
        $this->assertSame('base64:abc123', $writer->get('APP_KEY'));
        $this->assertSame('sqlite', $writer->get('DB_CONNECTION'));
    }

    public function test_set_appends_a_missing_key(): void
    {
        file_put_contents($this->path, "APP_NAME=Laravel\n");

        $writer = new EnvFileWriter($this->path);
        $writer->set(['DB_PASSWORD' => 'secret']);

        $this->assertSame('secret', $writer->get('DB_PASSWORD'));
        $this->assertSame('Laravel', $writer->get('APP_NAME'));
    }

    public function test_set_quotes_values_containing_spaces(): void
    {
        $writer = new EnvFileWriter($this->path);
        $writer->set(['APP_NAME' => 'Moje Craty']);

        $this->assertStringContainsString('APP_NAME="Moje Craty"', file_get_contents($this->path));
        $this->assertSame('Moje Craty', $writer->get('APP_NAME'));
    }

    public function test_get_returns_null_for_missing_key(): void
    {
        file_put_contents($this->path, "APP_NAME=Laravel\n");

        $this->assertNull((new EnvFileWriter($this->path))->get('DOES_NOT_EXIST'));
    }

    public function test_get_returns_empty_string_for_present_but_empty_key(): void
    {
        // Falsy, jak reszta appki oczekuje (np. `! $writer->get('APP_KEY')`
        // w public/index.php) — ale nie null, bo klucz faktycznie tam jest.
        file_put_contents($this->path, "APP_KEY=\n");

        $this->assertSame('', (new EnvFileWriter($this->path))->get('APP_KEY'));
        $this->assertFalse((bool) (new EnvFileWriter($this->path))->get('APP_KEY'));
    }
}
