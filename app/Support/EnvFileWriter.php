<?php

namespace App\Support;

/**
 * Bezpieczna edycja pliku .env — czyta plik, podmienia/dopisuje pojedyncze
 * klucze, zachowując resztę pliku bez zmian. Świadomie nie ładuje pliku
 * przez Dotenv/Laravela (ten kod działa też z `public/index.php`, zanim
 * framework w ogóle wystartuje — patrz EnsureAppKeyExists) i nie zależy od
 * niczego poza samym plikiem, żeby dało się jej użyć zarówno przed startem
 * Laravela (auto-generowanie APP_KEY), jak i w środku niego (Instalator).
 */
class EnvFileWriter
{
    public function __construct(private readonly string $path)
    {
    }

    /** Kopiuje $examplePath -> $this->path, jeśli plik docelowy jeszcze nie istnieje. */
    public function ensureExists(string $examplePath): void
    {
        if (! file_exists($this->path) && file_exists($examplePath)) {
            copy($examplePath, $this->path);
        }
    }

    public function exists(): bool
    {
        return file_exists($this->path);
    }

    public function isWritable(): bool
    {
        return $this->exists() ? is_writable($this->path) : is_writable(dirname($this->path));
    }

    public function get(string $key): ?string
    {
        if (preg_match($this->pattern($key), $this->read(), $matches)) {
            return trim($matches[1], " \t\"'");
        }

        return null;
    }

    /** @param  array<string, string>  $values */
    public function set(array $values): void
    {
        $contents = $this->read();

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->formatValue($value);
            $pattern = $this->pattern($key);

            $contents = preg_match($pattern, $contents)
                ? preg_replace($pattern, $line, $contents, 1)
                : rtrim($contents)."\n".$line."\n";
        }

        file_put_contents($this->path, $contents);
    }

    private function read(): string
    {
        return $this->exists() ? file_get_contents($this->path) : '';
    }

    private function pattern(string $key): string
    {
        return '/^'.preg_quote($key, '/').'=(.*)$/m';
    }

    /** Ten sam format co reszta pliku w tym repo: cudzysłów tylko, gdy wartość tego wymaga. */
    private function formatValue(string $value): string
    {
        return preg_match('/[\s#"]/', $value)
            ? '"'.str_replace('"', '\\"', $value).'"'
            : $value;
    }
}
