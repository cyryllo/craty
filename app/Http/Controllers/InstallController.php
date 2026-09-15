<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\User;
use App\Services\InstallerCleanupService;
use App\Support\EnvFileWriter;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/**
 * Kreator webowy do pierwszego uruchomienia na docelowym hostingu (patrz
 * TODO.md "Instalator aplikacji"). Świadomie zbudowany jako JEDNA strona
 * (kroki 1-5 to panele JS/Alpine, nie osobne trasy) zamiast wielostronicowego
 * kreatora z sesją trzymającą stan między krokami — appka przed instalacją
 * nie ma jeszcze bazy, a próba trzymania stanu kreatora w sesji byłaby
 * kruchym pomysłem. Formularz wysyła się raz, na końcu, do store().
 */
class InstallController extends Controller
{
    public function show(Request $request)
    {
        return view('install.wizard', [
            'requirements' => $this->checkRequirements(),
            'defaults' => [
                'db_host' => $request->old('db_host', '127.0.0.1'),
                'db_port' => $request->old('db_port', '3306'),
                // Zgadujemy z aktualnego żądania (schemat+host:port, którym
                // admin faktycznie dotarł do kreatora) — to dobry punkt
                // startowy, ale zostawiamy pole edytowalne na wypadek
                // reverse proxy/innej domeny publicznej niż to, co widzi
                // sam serwer aplikacji.
                'app_url' => $request->old('app_url', rtrim($request->getSchemeAndHttpHost(), '/')),
            ],
        ]);
    }

    /** AJAX pod przyciskiem "testuj połączenie" w kroku 2 — nic nie zapisuje. */
    public function testDatabase(Request $request)
    {
        $data = $request->validate($this->databaseRules());

        try {
            $this->connect($data);

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            ...$this->databaseRules(),
            'app_name' => ['nullable', 'string', 'max:255'],
            'app_url' => ['required', 'url', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'confirmed', Password::defaults()],
            'demo_data' => ['nullable', 'boolean'],
        ]);

        // Sprawdzamy połączenie jeszcze raz tuż przed zapisem — nie ufamy
        // wynikowi wcześniejszego AJAX-a z kroku 2 (mogło minąć sporo czasu).
        try {
            $this->connect($data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('installError',
                __('Could not connect to the database: :message', ['message' => $e->getMessage()]));
        }

        try {
            $this->writeEnvironment($data);
            $this->reconnectDatabaseConnection($data);

            // Czyścimy ewentualny stary cache configu PRZED migracją — inaczej appka
            // rozpakowana z paczki z zapisanym `config:cache` ignorowałaby świeży .env.
            Artisan::call('config:clear');
            Artisan::call('migrate', ['--force' => true]);

            // Bez tego linku zdjęcia przedmiotów i kody QR (dysk "public",
            // serwowany spod /storage/...) 404-owałyby na każdej świeżo
            // zainstalowanej appce — nikt wcześniej nie miał okazji tego
            // odpalić ręcznie, tak jak przy `composer create-project`.
            Artisan::call('storage:link');

            AppSetting::current()->fill(['name' => $data['app_name'] ?? null])->save();

            $admin = User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => $data['admin_password'],
                'role' => 'admin',
            ]);
            // forceFill: 'protected' celowo nie jest w $fillable (patrz UserController) —
            // główny admin założony przez Instalator musi być chroniony tak samo jak
            // dev-owy admin@craty.test z DatabaseSeeder.
            $admin->forceFill(['protected' => true])->save();

            if ($request->boolean('demo_data')) {
                Artisan::call('db:seed', ['--class' => DemoDataSeeder::class, '--force' => true]);
            }

            Artisan::call('config:clear');
        } catch (\Throwable $e) {
            // Instalacja może być bezpiecznie ponowiona — migracje już wykonane
            // zostaną pominięte, a .env i tak wskazuje na tę samą, prawdziwą bazę.
            return back()->withInput()->with('installError',
                __('Installation failed: :message. Fix the problem and submit the form again — steps already completed will simply be skipped.', ['message' => $e->getMessage()]));
        }

        // Flaga na sesji (nie flash() — strona podsumowania może obsłużyć
        // jeszcze jedno żądanie, "usuń pliki instalacyjne", więc musi
        // przeżyć więcej niż jeden kolejny request) — od teraz w bazie już
        // JEST użytkownik, więc EnsureNotInstalled zablokowałby /install/done
        // tak samo jak resztę kreatora, gdyby nie ten wyjątek na sesji.
        session(['justInstalled' => true, 'adminEmail' => $admin->email]);

        return redirect()->route('install.done');
    }

    public function done()
    {
        if (! session('justInstalled')) {
            return redirect()->route('login');
        }

        return view('install.done', ['adminEmail' => session('adminEmail')]);
    }

    /**
     * Usuwa pliki kreatora z serwera — opcjonalne domknięcie luki: sama
     * blokada EnsureNotInstalled trzyma się bazy (User::query()->exists()),
     * więc chwilowa awaria połączenia z bazą (catch (\Throwable) traktuje
     * to jako "jeszcze niezainstalowane") ponownie odsłoniłaby kreator,
     * dopóki te pliki istnieją. Chroniona tą samą flagą na sesji co done() —
     * nie EnsureNotInstalled, bo w tym momencie admin już istnieje.
     */
    public function cleanupFiles(InstallerCleanupService $cleanup)
    {
        if (! session('justInstalled')) {
            return redirect()->route('login');
        }

        $cleanup->removeInstallerFiles();

        Artisan::call('route:clear');
        Artisan::call('view:clear');

        session()->forget(['justInstalled', 'adminEmail']);

        return redirect()->route('login')
            ->with('status', __('Installer files removed. You can now log in.'));
    }

    /** @return array<string, mixed> */
    private function checkRequirements(): array
    {
        $extensions = ['pdo_mysql', 'mbstring', 'gd', 'zip', 'bcmath', 'exif', 'intl'];

        return [
            'php' => [
                'label' => __('PHP version (:version or newer)', ['version' => '8.2']),
                'ok' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'detail' => PHP_VERSION,
            ],
            'extensions' => collect($extensions)->map(fn ($ext) => [
                'label' => $ext,
                'ok' => extension_loaded($ext),
            ])->all(),
            'storage' => [
                'label' => __('Writable "storage" directory'),
                'ok' => is_writable(storage_path()),
            ],
            'bootstrap_cache' => [
                'label' => __('Writable "bootstrap/cache" directory'),
                'ok' => is_writable(base_path('bootstrap/cache')),
            ],
            'env' => [
                'label' => __('Writable .env file'),
                'ok' => app(EnvFileWriter::class)->isWritable(),
            ],
        ];
    }

    /** @return array<string, array<int, string>> */
    private function databaseRules(): array
    {
        return [
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function connect(array $data): void
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s', $data['db_host'], $data['db_port'], $data['db_database']);

        new \PDO($dsn, $data['db_username'], $data['db_password'] ?? '', [
            \PDO::ATTR_TIMEOUT => 5,
        ]);
    }

    /** @param  array<string, mixed>  $data */
    private function writeEnvironment(array $data): void
    {
        $writer = app(EnvFileWriter::class);
        $writer->ensureExists(base_path('.env.example'));

        $writer->set([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => (string) $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $data['db_password'] ?? '',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            // Bez tego appka zostaje na domyślnym APP_URL=http://localhost z
            // .env.example na stałe. Zdjęcia/QR w widokach już od tego nie
            // zależą (patrz config/filesystems.php — dysk "public" zwraca
            // ścieżki względne), ale APP_URL nadal ma znaczenie dla route()/
            // url() wywoływanych POZA kontekstem żądania HTTP (konsola,
            // zaplanowane zadania, przyszłe e-maile) — patrz
            // SetRequestForConsole w Laravelu.
            'APP_URL' => rtrim($data['app_url'], '/'),
        ]);
    }

    /**
     * Nadpisuje config('database...') w locie, tak jak MailSettingsApplier
     * robi to dla poczty — bez tego appka w TYM samym żądaniu (migracja,
     * tworzenie admina) nadal używałaby połączenia wczytanego przy starcie
     * procesu (domyślnie sqlite z .env.example), sprzed zapisania .env.
     * Instalator zawsze zapisuje DB_CONNECTION=mysql, więc też zawsze
     * przełącza się na połączenie "mysql", niezależnie od tego, co było
     * ustawione jako domyślne wcześniej.
     *
     * @param  array<string, mixed>  $data
     */
    private function reconnectDatabaseConnection(array $data): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $data['db_host'],
            'database.connections.mysql.port' => $data['db_port'],
            'database.connections.mysql.database' => $data['db_database'],
            'database.connections.mysql.username' => $data['db_username'],
            'database.connections.mysql.password' => $data['db_password'] ?? '',
        ]);

        DB::purge('mysql');
        DB::setDefaultConnection('mysql');
    }
}
