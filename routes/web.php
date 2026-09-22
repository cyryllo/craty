<?php

use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemImportController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MailSettingController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\ModuleSettingController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SaleListingController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StorageLocationController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Jedyna dziś publiczna (bez logowania) trasa w appce — patrz TODO.md.
// Świadomie poza grupą "auth" niżej; kontroler sam odpowiada 404, gdy admin
// nie włączył tej strony w Ustawienia → Ustawienia aplikacji.
Route::get('flea-market', [MarketplaceController::class, 'index'])->name('marketplace.index');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Osobisty wybór języka — dostępny z menu użytkownika dla każdej roli.
    Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

    // Uwaga na kolejność: "items/create" musi być zarejestrowane przed "items/{item}",
    // inaczej Laravel potraktuje "create" jako id przedmiotu i zwróci 404.

    // Wszystko, co zmienia stan magazynu, wymaga roli admin/magazynier.
    Route::middleware('role:admin,magazynier')->group(function () {
        // Rozdzielnik ustawień — sam widok pokazuje tylko karty, do których
        // dana rola ma dostęp; konkretne trasy nadal są osobno chronione niżej.
        Route::get('ustawienia', [SettingsController::class, 'index'])->name('settings.index');
        Route::get('items/create', [ItemController::class, 'create'])->name('items.create');
        Route::post('items', [ItemController::class, 'store'])->name('items.store');

        // Import masowy istniejącego spisu (TODO.md) — literały pod "items/",
        // więc muszą być zarejestrowane tu, przed wildcardem "items/{item}" niżej.
        Route::get('items/import', [ItemImportController::class, 'create'])->name('items.import.create');
        Route::post('items/import', [ItemImportController::class, 'store'])->name('items.import.store');
        Route::post('items/import/nieprzypisane', [ItemImportController::class, 'confirmUnassigned'])->name('items.import.confirm-unassigned');
        Route::get('items/import/szablon.csv', [ItemImportController::class, 'template'])->name('items.import.template');
        Route::get('items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
        Route::put('items/{item}', [ItemController::class, 'update'])->name('items.update');
        Route::delete('items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');
        Route::post('items/{item}/regenerate-qr', [ItemController::class, 'regenerateQr'])->name('items.regenerate-qr');
        Route::delete('items/{item}/photos/{photo}', [ItemController::class, 'destroyPhoto'])->name('items.photos.destroy');
        Route::delete('items/{item}/attachments/{attachment}', [ItemController::class, 'destroyAttachment'])->name('items.attachments.destroy');

        Route::post('items/{item}/loans', [LoanController::class, 'store'])->name('items.loans.store');
        Route::post('loans/{loan}/return', [LoanController::class, 'returnLoan'])->name('loans.return');

        // Moduł "Sprzedaż" (App\Support\Modules) — wyłączenie w Ustawieniach →
        // Moduły chowa tę grupę tras za 404, niezależnie od roli. `module:sales`
        // jest DOŁOŻONY na istniejący `role:admin,magazynier`, nie zamiast niego.
        Route::middleware('module:sales')->group(function () {
            Route::get('sales', [SaleListingController::class, 'index'])->name('sale-listings.index');
            Route::post('sales/{listing}/wystawiono', [SaleListingController::class, 'markListed'])->name('sale-listings.mark-listed');
            Route::get('sales/wystawione', [SaleListingController::class, 'exported'])->name('sale-listings.exported');
            Route::post('sales/wystawione/{listing}/sprzedano', [SaleListingController::class, 'markSold'])->name('sale-listings.mark-sold');
            Route::post('sales/wystawione/{listing}/wycofaj', [SaleListingController::class, 'withdraw'])->name('sale-listings.withdraw');
            Route::get('items/{item}/sale-listing/create', [SaleListingController::class, 'create'])->name('items.sale-listing.create');
            Route::post('items/{item}/sale-listing', [SaleListingController::class, 'store'])->name('items.sale-listing.store');
            Route::get('sales/eksport.csv', [SaleListingController::class, 'exportCsv'])->name('sale-listings.export');
        });

        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('warehouses', WarehouseController::class)->except('show');
        Route::resource('storage-locations', StorageLocationController::class)->except('show');

        // "Szybkie dodawanie" po nietrafionym skanie kodu kreskowego — mutuje
        // stan magazynu, więc te dwie trasy zostają w grupie admin/magazynier,
        // w przeciwieństwie do samego skanowania/podglądu niżej.
        Route::get('scan/quick-add', [ScanController::class, 'quickAddCreate'])->name('scan.quick-add.create');
        Route::post('scan/quick-add', [ScanController::class, 'quickAddStore'])->name('scan.quick-add.store');
    });

    // Ewidencję przedmiotów widzi każdy zalogowany, niezależnie od roli.
    Route::get('items', [ItemController::class, 'index'])->name('items.index');
    Route::get('items/{item}/label', [ItemController::class, 'label'])->name('items.label');
    Route::post('items/etykiety', [ItemController::class, 'printLabels'])->name('items.labels.print');
    Route::get('items/{item}', [ItemController::class, 'show'])->name('items.show');

    // Skanowanie kamerą (patrz TODO.md "PWA") — dostępne dla każdej roli, tak
    // samo jak sam podgląd przedmiotu po zeskanowaniu własnego QR z etykiety.
    Route::get('scan', [ScanController::class, 'show'])->name('scan.show');
    Route::get('scan/lookup', [ScanController::class, 'lookup'])->name('scan.lookup');

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::get('ustawienia/aplikacja', [AppSettingController::class, 'edit'])->name('settings.app.edit');
        Route::post('ustawienia/aplikacja', [AppSettingController::class, 'update'])->name('settings.app.update');

        Route::get('ustawienia/moduly', [ModuleSettingController::class, 'edit'])->name('settings.modules.edit');
        Route::post('ustawienia/moduly', [ModuleSettingController::class, 'update'])->name('settings.modules.update');

        Route::get('ustawienia/powiadomienia', [NotificationSettingController::class, 'edit'])->name('settings.notifications.edit');
        Route::post('ustawienia/powiadomienia', [NotificationSettingController::class, 'update'])->name('settings.notifications.update');

        Route::get('ustawienia/poczta', [MailSettingController::class, 'edit'])->name('settings.mail.edit');
        Route::post('ustawienia/poczta', [MailSettingController::class, 'update'])->name('settings.mail.update');
        Route::post('ustawienia/poczta/test', [MailSettingController::class, 'test'])->name('settings.mail.test');

        Route::get('ustawienia/aktualizacje', [UpdateController::class, 'index'])->name('settings.updates.index');
        // password.confirm: ponowne podanie hasła tuż przed jedną z najbardziej
        // uprzywilejowanych akcji w appce (nadpisanie własnego kodu PHP) —
        // ten sam mechanizm Breeze co przy zwykłej zmianie hasła.
        Route::post('ustawienia/aktualizacje', [UpdateController::class, 'upload'])
            ->middleware('password.confirm')->name('settings.updates.upload');
        // GET, żeby dało się "przećwiczyć" cały cykl potwierdzenia hasła (przejście
        // na /confirm-password i z powrotem) PRZED wybraniem pliku — patrz komentarz
        // przy $passwordConfirmed w UpdateController::index().
        Route::get('ustawienia/aktualizacje/potwierdz-haslo', [UpdateController::class, 'confirmed'])
            ->middleware('password.confirm')->name('settings.updates.confirm');
    });
});

require __DIR__.'/auth.php';
require __DIR__.'/install.php';
