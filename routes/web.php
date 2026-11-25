<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConsumableController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataTransferController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanGroupController;
use App\Http\Controllers\LoanProtocolController;
use App\Http\Controllers\LoansOverviewController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MachineBookingController;
use App\Http\Controllers\MailSettingController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\ModuleSettingController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SaleListingController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ShopSettingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StocktakeController;
use App\Http\Controllers\StorageLocationController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseStructureController;
use Illuminate\Support\Facades\Route;

// Jedyne publiczne (bez logowania) trasy w appce — patrz TODO.md. Świadomie
// poza grupą "auth" niżej. Strona główna to pchli targ: przy wyłączonym
// pokazuje tylko informację, że nic nie jest wystawione (plus kłódkę do
// logowania w nagłówku); strona pojedynczej oferty daje wtedy 404.
Route::get('/', [MarketplaceController::class, 'index'])->name('marketplace.index');
Route::get('offer/{listing}', [MarketplaceController::class, 'show'])->name('marketplace.show');

// Manifest PWA (instalacja aplikacji na telefonie) — publiczny, przeglądarka
// pobiera go jeszcze przed zalogowaniem. Patrz ManifestController.
Route::get('manifest.webmanifest', ManifestController::class)->name('pwa.manifest');

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

        // Eksport przedmiotów do CSV (z filtrami listy) — literał pod "items/",
        // więc musi być tu, przed wildcardem "items/{item}" niżej.
        Route::get('items/eksport.csv', [ItemController::class, 'export'])->name('items.export');

        // Strona "Import i eksport" (link z Ustawień). Kontakty, materiały i
        // historia wypożyczeń są dodatkowo za swoimi modułami.
        Route::get('import-eksport', [DataTransferController::class, 'index'])->name('data-transfer.index');
        Route::post('import-eksport/przedmioty', [DataTransferController::class, 'previewItems'])->name('data-transfer.items.preview');
        Route::post('import-eksport/przedmioty/zatwierdz', [DataTransferController::class, 'applyItems'])->name('data-transfer.items.apply');
        Route::middleware('module:contacts')->group(function () {
            Route::get('import-eksport/kontakty.csv', [DataTransferController::class, 'exportContacts'])->name('data-transfer.contacts.export');
            Route::post('import-eksport/kontakty', [DataTransferController::class, 'importContacts'])->name('data-transfer.contacts.import');
        });
        Route::middleware('module:consumables')->group(function () {
            Route::get('import-eksport/materialy.csv', [DataTransferController::class, 'exportConsumables'])->name('data-transfer.consumables.export');
            Route::post('import-eksport/materialy', [DataTransferController::class, 'importConsumables'])->name('data-transfer.consumables.import');
        });
        Route::get('import-eksport/wypozyczenia.csv', [DataTransferController::class, 'exportLoans'])->middleware('module:loans')->name('data-transfer.loans.export');
        Route::get('items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
        Route::put('items/{item}', [ItemController::class, 'update'])->name('items.update');
        Route::delete('items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');
        Route::post('items/{item}/regenerate-qr', [ItemController::class, 'regenerateQr'])->name('items.regenerate-qr');
        Route::delete('items/{item}/photos/{photo}', [ItemController::class, 'destroyPhoto'])->name('items.photos.destroy');
        Route::patch('items/{item}/photos/{photo}/move', [ItemController::class, 'movePhoto'])->name('items.photos.move');
        Route::delete('items/{item}/attachments/{attachment}', [ItemController::class, 'destroyAttachment'])->name('items.attachments.destroy');

        // Moduł "Wypożyczenia" (domyślnie włączony): wypożyczenia, zestawy,
        // rezerwacje na dni i protokoły; 404 przy wyłączonym module.
        Route::middleware('module:loans')->group(function () {
            Route::post('items/{item}/loans', [LoanController::class, 'store'])->name('items.loans.store');
            Route::post('loans/{loan}/return', [LoanController::class, 'returnLoan'])->name('loans.return');
            // Protokoły PDF działają też bez modułu Kontakty (wtedy z samą nazwą "komu").
            Route::get('loans/{loan}/protokol-wydania.pdf', [LoanProtocolController::class, 'issue'])->name('loans.protocol.issue');
            Route::get('loans/{loan}/protokol-zwrotu.pdf', [LoanProtocolController::class, 'returned'])->name('loans.protocol.return');

            // Strona "Wypożyczenia", zestawy (kilka przedmiotów, jeden protokół) i rezerwacje.
            Route::get('loans', [LoansOverviewController::class, 'index'])->name('loans.index');
            Route::get('loan-groups/create', [LoanGroupController::class, 'create'])->name('loan-groups.create');
            Route::post('loan-groups', [LoanGroupController::class, 'store'])->name('loan-groups.store');
            Route::get('loan-groups/{group}', [LoanGroupController::class, 'show'])->name('loan-groups.show');
            Route::post('loan-groups/{group}/return', [LoanGroupController::class, 'returnItems'])->name('loan-groups.return');
            Route::get('loan-groups/{group}/protokol-wydania.pdf', [LoanProtocolController::class, 'groupIssue'])->name('loan-groups.protocol.issue');
            Route::get('loan-groups/{group}/protokol-zwrotu.pdf', [LoanProtocolController::class, 'groupReturned'])->name('loan-groups.protocol.return');
            Route::post('items/{item}/reservations', [ReservationController::class, 'store'])->name('items.reservations.store');
            Route::delete('reservations/{reservation}', [ReservationController::class, 'destroy'])->name('reservations.destroy');
            Route::post('reservations/{reservation}/issue', [ReservationController::class, 'issue'])->name('reservations.issue');
        });

        // Moduł "Kontakty" — tak jak Sprzedaż: 404 przy wyłączonym module,
        // dołożone na role:admin,magazynier.
        Route::middleware('module:contacts')->group(function () {
            Route::resource('contacts', ContactController::class);
        });

        // Moduł "Materiały eksploatacyjne" (domyślnie wyłączony).
        Route::middleware('module:consumables')->group(function () {
            Route::resource('consumables', ConsumableController::class);
            Route::post('consumables/{consumable}/adjust', [ConsumableController::class, 'adjust'])->name('consumables.adjust');
        });

        // Moduł "Serwis": naprawy, przeglądy okresowe, gwarancje (domyślnie wyłączony).
        Route::middleware('module:service')->group(function () {
            Route::get('service', [ServiceController::class, 'index'])->name('service.index');
            Route::post('items/{item}/repairs', [RepairController::class, 'store'])->name('items.repairs.store');
            Route::post('repairs/{repair}/complete', [RepairController::class, 'complete'])->name('repairs.complete');
            Route::post('items/{item}/inspections', [InspectionController::class, 'store'])->name('items.inspections.store');
        });

        // Moduł "Sprzedaż" (App\Support\Modules) — wyłączenie w Ustawieniach →
        // Moduły chowa tę grupę tras za 404, niezależnie od roli. `module:sales`
        // jest DOŁOŻONY na istniejący `role:admin,magazynier`, nie zamiast niego.
        Route::middleware('module:sales')->group(function () {
            Route::get('sales', [SaleListingController::class, 'index'])->name('sale-listings.index');
            Route::get('sales/{listing}/edit', [SaleListingController::class, 'edit'])->name('sale-listings.edit');
            Route::put('sales/{listing}', [SaleListingController::class, 'update'])->name('sale-listings.update');
            Route::patch('sales/{listing}/link', [SaleListingController::class, 'updateLink'])->name('sale-listings.update-link');
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
        Route::get('warehouse-structure', [WarehouseStructureController::class, 'index'])->name('warehouse-structure.index');
        Route::resource('rooms', RoomController::class)->except('show');
        Route::resource('storage-locations', StorageLocationController::class)->except('show');

        // Inwentaryzacja (skan miejsca, raport braków i rzeczy nie na miejscu).
        Route::get('stocktakes', [StocktakeController::class, 'index'])->name('stocktakes.index');
        Route::post('stocktakes', [StocktakeController::class, 'store'])->name('stocktakes.store');
        Route::get('stocktakes/{stocktake}', [StocktakeController::class, 'show'])->name('stocktakes.show');
        Route::get('stocktakes/{stocktake}/raport.csv', [StocktakeController::class, 'reportCsv'])->name('stocktakes.report-csv');
        Route::post('stocktakes/{stocktake}/scan', [StocktakeController::class, 'scan'])->name('stocktakes.scan');
        Route::post('stocktakes/{stocktake}/finish', [StocktakeController::class, 'finish'])->name('stocktakes.finish');
        Route::post('stocktakes/{stocktake}/move/{item}', [StocktakeController::class, 'move'])->name('stocktakes.move');
        Route::delete('stocktakes/{stocktake}', [StocktakeController::class, 'destroy'])->name('stocktakes.destroy');

        // "Szybkie dodawanie" po nietrafionym skanie kodu kreskowego — mutuje
        // stan magazynu, więc te dwie trasy zostają w grupie admin/magazynier,
        // w przeciwieństwie do samego skanowania/podglądu niżej.
        Route::get('scan/quick-add', [ScanController::class, 'quickAddCreate'])->name('scan.quick-add.create');
        Route::post('scan/quick-add', [ScanController::class, 'quickAddStore'])->name('scan.quick-add.store');
    });

    // Ewidencja przedmiotów i skanowanie: cała obsługa (admin, magazynier),
    // ale nie rola Użytkownik, która widzi tylko rezerwacje maszyn. Kiedyś
    // "każdy zalogowany", co przy samej obsłudze znaczyło to samo.
    Route::middleware('role:admin,magazynier')->group(function () {
        Route::get('items', [ItemController::class, 'index'])->name('items.index');
        Route::get('items/{item}/label', [ItemController::class, 'label'])->name('items.label');
        Route::post('items/etykiety', [ItemController::class, 'printLabels'])->name('items.labels.print');
        Route::get('items/{item}', [ItemController::class, 'show'])->name('items.show');

        // Skanowanie kamerą (patrz TODO.md "PWA"), tak samo jak sam podgląd
        // przedmiotu po zeskanowaniu własnego QR z etykiety.
        Route::get('scan', [ScanController::class, 'show'])->name('scan.show');
        Route::get('scan/lookup', [ScanController::class, 'lookup'])->name('scan.lookup');
        // Seria skanów: kolejne kody trafiają na listę (np. pod wypożyczenie zestawu).
        Route::get('scan/batch', [ScanController::class, 'batch'])->name('scan.batch');
        Route::get('scan/resolve', [ScanController::class, 'resolve'])->name('scan.resolve');
    });

    // Moduł "Rezerwacje maszyn": jedyne, co poza profilem widzi rola Użytkownik.
    Route::middleware(['role:admin,magazynier,uzytkownik', 'module:bookings'])->group(function () {
        Route::get('bookings', [MachineBookingController::class, 'index'])->name('bookings.index');
        Route::post('bookings', [MachineBookingController::class, 'store'])->name('bookings.store');
        Route::delete('bookings/{booking}', [MachineBookingController::class, 'destroy'])->name('bookings.destroy');
    });

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::get('ustawienia/aplikacja', [AppSettingController::class, 'edit'])->name('settings.app.edit');
        Route::post('ustawienia/aplikacja', [AppSettingController::class, 'update'])->name('settings.app.update');

        Route::middleware('module:sales')->group(function () {
            Route::get('ustawienia/opcje-sprzedazy', [ShopSettingController::class, 'edit'])->name('settings.shop.edit');
            Route::post('ustawienia/opcje-sprzedazy', [ShopSettingController::class, 'update'])->name('settings.shop.update');
        });

        Route::get('ustawienia/moduly', [ModuleSettingController::class, 'edit'])->name('settings.modules.edit');
        Route::post('ustawienia/moduly', [ModuleSettingController::class, 'update'])->name('settings.modules.update');

        // Kopie zapasowe (BackupService): pobieranie, wgrywanie i przywracanie po potwierdzeniu hasła.
        Route::get('ustawienia/kopie', [BackupController::class, 'index'])->name('settings.backups.index');
        Route::post('ustawienia/kopie', [BackupController::class, 'store'])->name('settings.backups.store');
        Route::post('ustawienia/kopie/ustawienia', [BackupController::class, 'settings'])->name('settings.backups.settings');
        Route::get('ustawienia/kopie/potwierdz', [BackupController::class, 'confirmed'])->middleware('password.confirm')->name('settings.backups.confirm');
        Route::post('ustawienia/kopie/wgraj', [BackupController::class, 'upload'])->middleware('password.confirm')->name('settings.backups.upload');
        Route::get('ustawienia/kopie/{name}/pobierz', [BackupController::class, 'download'])->middleware('password.confirm')->name('settings.backups.download');
        Route::post('ustawienia/kopie/{name}/przywroc', [BackupController::class, 'restore'])->middleware('password.confirm')->name('settings.backups.restore');
        Route::delete('ustawienia/kopie/{name}', [BackupController::class, 'destroy'])->name('settings.backups.destroy');

        // Dziennik aktywności (logowania, konta, ustawienia; opcjonalnie zmiany danych).
        Route::get('ustawienia/dziennik', [ActivityLogController::class, 'index'])->name('settings.activity.index');
        Route::get('ustawienia/dziennik/eksport.csv', [ActivityLogController::class, 'export'])->name('settings.activity.export');
        Route::post('ustawienia/dziennik', [ActivityLogController::class, 'update'])->name('settings.activity.update');

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
