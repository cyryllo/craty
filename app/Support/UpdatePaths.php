<?php

namespace App\Support;

/**
 * Dwie różne listy ścieżek dla dwóch różnych momentów Modułu Aktualizacje:
 * czym appka JEST (do spakowania) kontra co WOLNO nadpisać na docelowym
 * serwerze (znacznie krótsza lista — patrz TODO.md "Moduł Aktualizacje").
 */
class UpdatePaths
{
    /**
     * Wykluczone z paczki .zip — zarówno tej budowanej przez `release:build`
     * do dystrybucji, jak i własnej migawki kodu robionej przez
     * UpdateService tuż przed zastosowaniem aktualizacji (ta sama lista,
     * bo obie mają reprezentować "całą działającą appkę" z tym samym
     * wyjątkiem plików deweloperskich/danych użytkownika). `vendor/` i
     * `public/build` świadomie NIE są tu wykluczone — mają być w paczce,
     * żeby target nie potrzebował composera/npm.
     */
    public const PACKAGE_EXCLUDES = [
        '.git',
        '.env',
        '.env.testing',
        '.gitignore',
        'node_modules',
        'tests',
        'docker-compose.yml',
        'Dockerfile',
        'art',
        'composer',
        'test',
        'storage/app/public',
        'storage/app/backups',
        'storage/app/updates',
        'storage/logs',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
    ];

    /**
     * Nigdy nie nadpisywane przy podmianie plików „na żywo" — ani przy
     * zastosowaniu aktualizacji, ani przy rollbacku. Krótka, świadomie
     * zawężona lista (patrz specyfikacja): .env i dane, których nie ma w
     * żadnej paczce/migawce kodu (bo są wykluczone wyżej), więc nadpisanie
     * i tak by ich nie dotyczyło — ale trzymamy to jako osobną, jawną listę
     * na wypadek, gdyby ktoś kiedyś rozszerzył PACKAGE_EXCLUDES i przypadkiem
     * zaczął pakować np. storage/app/public.
     */
    public const PROTECTED_PATHS = [
        '.env',
        'storage/app/public',
        'storage/logs',
        'public/storage',
    ];
}
