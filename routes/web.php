<?php

use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SaleListingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StorageLocationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

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
        Route::get('items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
        Route::put('items/{item}', [ItemController::class, 'update'])->name('items.update');
        Route::delete('items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');

        Route::post('items/{item}/loans', [LoanController::class, 'store'])->name('items.loans.store');
        Route::post('loans/{loan}/return', [LoanController::class, 'returnLoan'])->name('loans.return');

        Route::get('sprzedaz', [SaleListingController::class, 'index'])->name('sale-listings.index');
        Route::get('sprzedaz/wystawione', [SaleListingController::class, 'exported'])->name('sale-listings.exported');
        Route::post('sprzedaz/wystawione/{listing}/sprzedano', [SaleListingController::class, 'markSold'])->name('sale-listings.mark-sold');
        Route::get('items/{item}/sale-listing/create', [SaleListingController::class, 'create'])->name('items.sale-listing.create');
        Route::post('items/{item}/sale-listing', [SaleListingController::class, 'store'])->name('items.sale-listing.store');
        Route::get('sprzedaz/eksport.csv', [SaleListingController::class, 'exportCsv'])->name('sale-listings.export');

        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('warehouses', WarehouseController::class)->except('show');
        Route::resource('storage-locations', StorageLocationController::class)->except('show');
    });

    // Ewidencję przedmiotów widzi każdy zalogowany, niezależnie od roli (rola "podglad" = tylko odczyt).
    Route::get('items', [ItemController::class, 'index'])->name('items.index');
    Route::get('items/{item}/label', [ItemController::class, 'label'])->name('items.label');
    Route::get('items/{item}', [ItemController::class, 'show'])->name('items.show');

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::get('ustawienia/aplikacja', [AppSettingController::class, 'edit'])->name('settings.app.edit');
        Route::post('ustawienia/aplikacja', [AppSettingController::class, 'update'])->name('settings.app.update');
    });
});

require __DIR__.'/auth.php';
