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
        '.gitattributes',
        '.editorconfig',
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
        // Dokumentacja/meta tego repo — nie jest kodem appki, nie ma czego
        // szukać na docelowym serwerze. CLAUDE.md w szczególności NIE ma
        // prawa nigdzie wyciekać (patrz .gitignore) — bez tego wpisu i tak
        // trafiał do paczki, bo builder zipuje realne pliki na dysku, a nie
        // to, co jest w gicie (gitignore go nie chroni przed spakowaniem).
        'CLAUDE.md',
        'README.md',
        'README.en.md',
        'CHANGELOG.md',
        'TODO.md',
        'LICENSE',
        // Konfiguracja narzędzi budujących/testujących, zbędna po tym, jak
        // `public/build` jest już skompilowane, a `vendor/` już zvendorowany
        // — na docelowym hostingu i tak nikt nie odpali composera ani npm.
        'package.json',
        'package-lock.json',
        'composer.json',
        'composer.lock',
        'phpunit.xml',
        'vite.config.js',
        'tailwind.config.js',
        'postcss.config.js',
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
