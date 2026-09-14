<?php

namespace App\Support;

/**
 * Wersja appki żyje w płaskim pliku VERSION w korzeniu repo, nie w bazie ani
 * w config/app.php — musi dać się ją odczytać i nadpisać niezależnie od
 * tego, czy baza w ogóle działa (Moduł Aktualizacje robi to na etapie, gdy
 * migracje mogły się jeszcze nie udać) i bez czyszczenia cache configu.
 */
class AppVersion
{
    public function __construct(private readonly string $path)
    {
    }

    public function current(): string
    {
        return file_exists($this->path) ? trim(file_get_contents($this->path)) : '0.0.0';
    }

    public function set(string $version): void
    {
        file_put_contents($this->path, trim($version)."\n");
    }
}
