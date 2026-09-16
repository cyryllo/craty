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
     * Wykluczone z paczki .zip budowanej przez `release:build` do
     * dystrybucji — pliki deweloperskie i dane użytkownika nie mają czego
     * szukać w paczce reprezentującej "całą działającą appkę". `vendor/` i
     * `build/` świadomie NIE są tu wykluczone — mają być w paczce, żeby
     * target nie potrzebował composera/npm.
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
        'app-storage/app/public',
        'app-storage/app/updates',
        // Wyjście samego release:build — bez tego każde kolejne wydanie
        // pakowałoby ze sobą wszystkie poprzednie .zip-y z tego katalogu
        // (znalezione realnie: paczka 1.1.0 spuchła do 49 MB, bo wciągnęła
        // w środek całą paczkę 1.0.1).
        'app-storage/app/releases',
        'app-storage/logs',
        'app-storage/framework/cache',
        'app-storage/framework/sessions',
        'app-storage/framework/views',
        // Fixtures ze Storage::fake() zostawione przez uruchomienia testów na
        // maszynie budującej — czysto lokalny śmieć, zero związku z appką.
        'app-storage/framework/testing',
        // Symlink "storage" (patrz PROTECTED_PATHS) — na maszynie budującej
        // wskazuje już na prawdziwe zdjęcia (app-storage/app/public, i tak
        // wykluczone wyżej); pakowanie go osobno tylko dublowałoby te same
        // pliki pod drugą nazwą w zipie.
        'storage',
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
        // `build/` jest już skompilowane, a `vendor/` już zvendorowany —
        // na docelowym hostingu i tak nikt nie odpali composera ani npm.
        // UWAGA: composer.json celowo NIE jest na tej liście — Laravel czyta
        // go w RUNTIME, nie tylko przy `composer install` (Application::
        // getNamespace() przy każdym starcie artisan/serve, PackageManifest
        // przy odświeżaniu cache pakietów) — bez niego appka wywala się od
        // razu błędem "file_get_contents(composer.json): No such file or
        // directory", zanim cokolwiek zdąży odpowiedzieć (znalezione realnie
        // przy teście paczki na świeżo rozpakowanym katalogu).
        // composer.lock nie ma tego problemu (używany tylko przez sam
        // composer, nie przez framework), zostaje wykluczony.
        'package.json',
        'package-lock.json',
        'composer.lock',
        'phpunit.xml',
        '.phpunit.result.cache',
        'vite.config.js',
        'tailwind.config.js',
        'postcss.config.js',
    ];

    /**
     * Nigdy nie nadpisywane przy podmianie plików „na żywo" przy
     * zastosowaniu aktualizacji. Krótka, świadomie zawężona lista (patrz
     * specyfikacja): `.env` i dane użytkownika, których nie ma w żadnej
     * paczce (bo są wykluczone wyżej), więc nadpisanie i tak by ich nie
     * dotyczyło — ale trzymamy to jako osobną, jawną listę na wypadek,
     * gdyby ktoś kiedyś rozszerzył PACKAGE_EXCLUDES i przypadkiem zaczął
     * pakować np. app-storage/app/public. `storage` chroni sam SYMLINK do
     * zdjęć/QR (public_path('storage')) — appka nie ma osobnego document
     * rootu, więc pakiet mógłby w teorii próbować nadpisać go zwykłym
     * plikiem/katalogiem, co zerwałoby wszystkie URL-e do zdjęć.
     */
    public const PROTECTED_PATHS = [
        '.env',
        'app-storage/app/public',
        'app-storage/logs',
        'storage',
    ];
}
