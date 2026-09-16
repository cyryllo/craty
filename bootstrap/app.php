<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Appka ma jeden, stały, spłaszczony układ (patrz CLAUDE.md "Project
// layout"): korzeń repo jest jednocześnie document rootem i katalogiem
// appki — bez osobnego public/. usePublicPath() sprawia, że public_path()
// == base_path() wszędzie (index.php w korzeniu, Vite pisze do <root>/build,
// GeneratePwaIcons do <root>/icons itd.), a useStoragePath() przenosi
// prawdziwy storage/ Laravela do app-storage/, żeby zwolnić nazwę "storage"
// w korzeniu wyłącznie pod symlink do zdjęć/QR (public_path('storage'),
// tworzony przez `artisan storage:link`) — inaczej realny katalog i cel
// symlinku nazywałyby się tak samo i kolidowały. Musi to siedzieć TU, nie
// tylko w index.php, żeby KAŻDY punkt wejścia (HTTP, każda komenda artisan,
// testy) widział te same ścieżki.
return tap(Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'install.guard' => \App\Http\Middleware\EnsureNotInstalled::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\RedirectToInstallerIfNotInstalled::class,
            \App\Http\Middleware\SetLocale::class,
        ]);
        // Bez tego "auth" (domyślna lista priorytetów Laravela odwołuje się
        // do NIEGO przez interfejs AuthenticatesRequests, nie konkretną
        // klasę Authenticate — dlatego kotwiczymy o ten sam interfejs) mogło
        // wykonać się PRZED naszym middlewarem na trasach typu /dashboard —
        // gość trafiał na /login zamiast na /install, mimo braku
        // zainstalowanej appki.
        $middleware->prependToPriorityList(
            before: \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            prepend: \App\Http\Middleware\RedirectToInstallerIfNotInstalled::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create(), function (Application $app) {
        $app->usePublicPath($app->basePath());
        $app->useStoragePath($app->basePath('app-storage'));
    });
