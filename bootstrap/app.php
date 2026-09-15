<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
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
    })->create();
