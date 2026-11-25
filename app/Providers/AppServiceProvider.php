<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\Item;
use App\Models\User;
use App\Observers\AppSettingActivityObserver;
use App\Observers\DataActivityObserver;
use App\Observers\ItemObserver;
use App\Observers\UserActivityObserver;
use App\Services\InstallerCleanupService;
use App\Support\AppVersion;
use App\Support\EnvFileWriter;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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

        // Dziennik aktywności: konta i ustawienia zawsze, dane tylko po włączeniu
        // (DataActivityObserver sam sprawdza ustawienie), logowania przez zdarzenia Laravela.
        User::observe(UserActivityObserver::class);
        AppSetting::observe(AppSettingActivityObserver::class);
        foreach (array_keys(ActivityLog::SUBJECTS) as $model) {
            $model::observe(DataActivityObserver::class);
        }
        Event::listen(Login::class, fn (Login $e) => ActivityLog::record('auth.login', null, [], $e->user));
        Event::listen(Logout::class, fn (Logout $e) => $e->user ? ActivityLog::record('auth.logout', null, [], $e->user) : null);
        // Nieudane logowanie: bez przypisania do konta (to nie ono coś zrobiło), z wpisanym e-mailem.
        Event::listen(Failed::class, fn (Failed $e) => ActivityLog::record('auth.failed', null, [], null, (string) ($e->credentials['email'] ?? '')));
        Event::listen(Lockout::class, fn (Lockout $e) => ActivityLog::record('auth.lockout', null, [], null, (string) $e->request->input('email')));
        Event::listen(PasswordReset::class, fn (PasswordReset $e) => ActivityLog::record('auth.password_reset', null, [], $e->user));

        // Domyślne wymagania dla KAŻDEGO Password::defaults() w appce —
        // zmiana hasła w Profilu, reset hasła, konto głównego admina z
        // Instalatora, konto zakładane/edytowane przez admina w
        // Ustawienia → Użytkownicy.
        Password::defaults(fn () => Password::min(8)->mixedCase()->symbols());
    }
}
