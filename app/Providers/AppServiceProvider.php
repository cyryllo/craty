<?php

namespace App\Providers;

use App\Models\Item;
use App\Observers\ItemObserver;
use App\Services\InstallerCleanupService;
use App\Support\AppVersion;
use App\Support\EnvFileWriter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ścieżka konfigurowalna (nie zawsze base_path('.env')), żeby
        // testy Instalatora (InstallController) mogły podmienić ją na
        // plik tymczasowy zamiast pisać po prawdziwym .env tego repo.
        $this->app->bind(EnvFileWriter::class, fn () => new EnvFileWriter(
            config('app.env_file_path', base_path('.env'))
        ));

        // To samo dla VERSION — testy Modułu Aktualizacje operują na kopii
        // appki w katalogu tymczasowym, nie na tym repo.
        $this->app->bind(AppVersion::class, fn () => new AppVersion(
            config('app.version_file_path', base_path('VERSION'))
        ));

        // Ten sam klucz co UpdateService::appRoot() — oba opisują "katalog,
        // w którym leży ta appka", i oba muszą dać się podmienić w testach
        // na katalog tymczasowy (ta usługa kasuje pliki appki).
        $this->app->bind(InstallerCleanupService::class, fn () => new InstallerCleanupService(
            config('app.update_root_path', base_path())
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Item::observe(ItemObserver::class);
    }
}
