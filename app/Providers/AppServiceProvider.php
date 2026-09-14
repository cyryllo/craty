<?php

namespace App\Providers;

use App\Models\Item;
use App\Observers\ItemObserver;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Item::observe(ItemObserver::class);
    }
}
