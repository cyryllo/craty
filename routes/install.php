<?php

use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

// Instalator webowy — patrz TODO.md "Instalator aplikacji". Cała grupa
// (poza /install/done, patrz kontroler) chroniona przez EnsureNotInstalled:
// jeśli w bazie istnieje już jakikolwiek użytkownik, każde wejście tutaj
// przekierowuje na /login zamiast pokazywać kreator drugi raz.
Route::middleware('install.guard')->group(function () {
    Route::get('install', [InstallController::class, 'show'])->name('install.show');
    Route::post('install/test-database', [InstallController::class, 'testDatabase'])->name('install.test-database');
    Route::post('install', [InstallController::class, 'store'])->name('install.store');
});

// Poza EnsureNotInstalled świadomie — w momencie, gdy tu trafiamy, admin
// JUŻ istnieje (store() go właśnie założył), więc ta sama blokada
// przekierowałaby stąd prosto na /login. Zamiast tego chroni je flaga na
// sesji, ustawiana wyłącznie przez store() (patrz InstallController::done()).
Route::get('install/done', [InstallController::class, 'done'])->name('install.done');
Route::post('install/done/cleanup', [InstallController::class, 'cleanupFiles'])->name('install.cleanup');
