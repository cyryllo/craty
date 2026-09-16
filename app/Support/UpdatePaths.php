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
        // Wyjście samego release:build — bez tego każde kolejne wydanie
        // pakowałoby ze sobą wszystkie poprzednie .zip-y z tego katalogu
        // (znalezione realnie: paczka 1.1.0 spuchła do 49 MB, bo wciągnęła
        // w środek całą paczkę 1.0.1). To samo dotyczy migawki kodu, którą
        // UpdateService robi tuż przed apply() — również nie ma czego
        // pakować sam w siebie.
        'storage/app/releases',
        'storage/logs',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        // Fixtures ze Storage::fake() zostawione przez uruchomienia testów na
        // maszynie budującej — czysto lokalny śmieć, zero związku z appką.
        'storage/framework/testing',
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
        // UWAGA: composer.json celowo NIE jest na tej liście — Laravel czyta
        // go w RUNTIME, nie tylko przy `composer install` (Application::
        // getNamespace() przy każdym starcie artisan/serve, PackageManifest
        // przy odświeżaniu cache pakietów) — bez niego appka wywala się od
        // razu błędem "file_get_contents(composer.json): No such file or
        // directory", zanim cokolwiek zdąży odpowiedzieć (znalezione realnie
        // przy teście paczki "-full" na świeżo rozpakowanym katalogu).
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

    /**
     * Ta sama rola co PROTECTED_PATHS wyżej, ale dla instalacji spłaszczonej
     * przez `release:build-hosting` (open_basedir ograniczony do
     * document rootu — patrz BuildHostingPackage). Tam `storage/` appki
     * nazywa się `app-storage/` (patrz renameStorageDirectory()), a
     * "storage" pod document rootem to SYMLINK, nie katalog — musi zostać
     * chroniony tak samo jak `.env`, żeby paczka zbudowana na maszynie
     * deweloperskiej (gdzie tego symlinku jeszcze nie ma) nigdy nie
     * spróbowała nadpisać go czymkolwiek.
     *
     * UpdateService sam wybiera, której listy użyć — auto-wykrywając
     * układ instalacji po istnieniu katalogu `app-storage` w korzeniu
     * appki, patrz UpdateService::protectedPaths(). Admin nie musi niczego
     * zaznaczać w panelu.
     */
    public const PROTECTED_PATHS_FLATTENED = [
        '.env',
        'app-storage/app/public',
        'app-storage/logs',
        'storage',
    ];
}
