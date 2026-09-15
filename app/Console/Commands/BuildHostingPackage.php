<?php

namespace App\Console\Commands;

use App\Services\UpdatePackageBuilder;
use App\Support\AppVersion;
use App\Support\UpdatePaths;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

/**
 * Wariant paczki instalacyjnej dla hostingów, które trzymają PHP w klatce
 * open_basedir ograniczonej wyłącznie do document rootu (realnie napotkane:
 * DirectAdmin, `open_basedir` = sam `public_html` + `/tmp` + kilka systemowych
 * ścieżek). Normalna paczka "-full" (release:build --output=...) zakłada, że
 * kod appki leży JEDEN KATALOG WYŻEJ niż document root (public/) — tak jak
 * każdy poprawny deploy Laravela — ale gdy open_basedir nie pozwala PHP-owi
 * wyjść poza sam document root, appka w ogóle nie ma prawa tam sięgnąć,
 * niezależnie od uprawnień plików. Jedyne wyjście: cała appka ląduje
 * bezpośrednio w document roocie.
 *
 * Ta komenda spłaszcza strukturę: zawartość `public/` ląduje w korzeniu
 * paczki (czyli docelowo bezpośrednio w document rootcie hostingu),
 * `index.php` dostaje przepisane ścieżki (bez `../`), a każdy katalog z
 * kodem/danymi (app/, bootstrap/, config/, database/, lang/, resources/,
 * routes/, storage/, vendor/) dostaje własny `.htaccess` blokujący
 * bezpośredni dostęp z przeglądarki — bez tego kod źródłowy i `.env` (hasła
 * do bazy, APP_KEY!) byłyby publicznie pobieralne pod prostym adresem URL.
 * Mniej bezpieczne strukturalnie niż rozdzielenie kodu i document rootu (to
 * .htaccess musi akurat zadziałać — nie zadziała na Nginx albo z wyłączonym
 * AllowOverride), ale to jedyna opcja, gdy hosting wymusza open_basedir.
 */
class BuildHostingPackage extends Command
{
    protected $signature = 'release:build-hosting
        {version? : Docelowa wersja (domyślnie ta już zapisana w pliku VERSION)}
        {--output= : Ścieżka wynikowego .zip (domyślnie storage/app/releases/craty-{wersja}-hosting.zip)}';

    protected $description = 'Buduje spłaszczoną paczkę .zip do rozpakowania wprost w document roocie hostingu (gdy open_basedir nie pozwala trzymać appki wyżej niż public_html).';

    /**
     * Katalogi z kodem/danymi appki — po spłaszczeniu dostają .htaccess
     * blokujący dostęp z przeglądarki. "storage" (prawdziwy katalog
     * Laravela — sesje/cache/logi/dane usera) zamienione na "app-storage"
     * — patrz renameStorageDirectory().
     */
    private const PROTECTED_DIRS = ['app', 'bootstrap', 'config', 'database', 'lang', 'resources', 'routes', 'app-storage', 'vendor'];

    /** Pliki w korzeniu, które nie mają prawa być pobierane wprost (hasła do bazy, klucz appki). */
    private const PROTECTED_ROOT_FILES_PATTERN = '^(\.env.*|composer\.(json|lock)|artisan|VERSION|update-manifest\.json|phpunit\.xml)$';

    public function handle(UpdatePackageBuilder $builder, AppVersion $appVersion): int
    {
        $version = $this->argument('version') ?: $appVersion->current();
        $output = $this->option('output') ?: storage_path("app/releases/craty-{$version}-hosting.zip");

        $this->info("Buduję spłaszczoną paczkę hostingową {$version}...");

        $workDir = storage_path('app/tmp-hosting-build-'.uniqid());
        $tempZip = $workDir.'.zip';

        try {
            // Ten sam builder i ta sama lista wykluczeń co zwykła paczka —
            // spłaszczamy dopiero POTEM, żeby nie duplikować (i nie rozjeżdżać
            // w dwóch miejscach) logiki tego, co appka w ogóle zawiera.
            $builder->build(base_path(), $tempZip, UpdatePaths::PACKAGE_EXCLUDES);
            $this->extract($tempZip, $workDir);

            $this->flattenPublicDirectory($workDir);
            $this->renameStorageDirectory($workDir);
            $this->rewriteIndexPhp($workDir.'/index.php');
            $this->writeHtaccessFiles($workDir);

            // Już spłaszczone i tak samo wyczyszczone jak źródło — zero
            // dodatkowych wykluczeń przy pakowaniu z powrotem.
            $builder->build($workDir, $output, []);

            $hash = $builder->writeChecksumFile($output);

            $this->info('Gotowe: '.$output);
            $this->line('SHA-256: '.$hash.' (zapisany też obok jako '.basename($output).'.sha256)');

            return self::SUCCESS;
        } finally {
            File::deleteDirectory($workDir);
            if (file_exists($tempZip)) {
                unlink($tempZip);
            }
        }
    }

    private function extract(string $zipPath, string $destination): void
    {
        $zip = new ZipArchive();
        $zip->open($zipPath);
        $zip->extractTo($destination);
        $zip->close();
    }

    private function flattenPublicDirectory(string $workDir): void
    {
        $publicDir = $workDir.'/public';

        foreach (scandir($publicDir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $from = $publicDir.'/'.$entry;
            $to = $workDir.'/'.$entry;

            is_dir($from) ? File::moveDirectory($from, $to) : File::move($from, $to);
        }

        File::deleteDirectory($publicDir);
    }

    /**
     * Po spłaszczeniu publicPath() == basePath() (patrz rewriteIndexPhp), więc
     * "storage" pod document rootem musi zostać SYMLINKIEM zrobionym przez
     * `artisan storage:link` (public_path('storage') -> storage_path(
     * 'app/public')) — ale prawdziwy katalog storage/ appki (sesje, cache,
     * logi) leży dosłownie w tym samym miejscu! `symlink()` nie ma prawa
     * podmienić istniejącego katalogu na link, więc storage:link wywalał się
     * cicho błędem "No such file or directory", zdjęcia/QR nigdy nie miały
     * czego serwować pod /storage/... (realnie złapany bug na produkcji —
     * puste 404 na wszystkich zdjęciach mimo poprawnych linków w HTML-u).
     * Rozwiązanie: prawdziwy storage appki przenosi się pod "app-storage" (i
     * $app->useStoragePath() w index.php), zwalniając "storage" wyłącznie
     * pod symlink do publicznych plików.
     */
    private function renameStorageDirectory(string $workDir): void
    {
        File::moveDirectory($workDir.'/storage', $workDir.'/app-storage');
    }

    /**
     * public/index.php zakłada, że appka leży katalog WYŻEJ ("__DIR__.'/../...'")
     * — po spłaszczeniu jest obok, więc "/.." znika. Druga zmiana jest mniej
     * oczywista: Laravel liczy publicPath() jako base_path().'/public' na
     * sztywno, niezależnie od tego, gdzie faktycznie leży index.php — Vite
     * (manifest.json, zbudowane assety) i asset() budują ścieżki właśnie
     * przez publicPath(). Bez jawnego $app->usePublicPath(__DIR__) appka
     * szukałaby manifestu pod .../build/public/build/manifest.json
     * (podwójne "public") i wywalała ViteManifestNotFoundException, zanim
     * cokolwiek zdążyło się wyrenderować — złapane realnie przy pierwszym
     * GET /install na spłaszczonej paczce na produkcji.
     */
    private function rewriteIndexPhp(string $path): void
    {
        $contents = str_replace("__DIR__.'/../", "__DIR__.'/", file_get_contents($path));

        // Prawdziwy storage/ appki przeniesiony na app-storage/ (patrz
        // renameStorageDirectory) — obie linijki w tym pliku, które jeszcze
        // wskazują na "storage" (sprawdzenie trybu konserwacji i
        // RequiredStorageDirectories::ensureExist), muszą pójść tam za nim.
        $contents = str_replace("__DIR__.'/storage", "__DIR__.'/app-storage", $contents);

        $contents = str_replace(
            "(require_once __DIR__.'/bootstrap/app.php')\n    ->handleRequest(Request::capture());",
            "\$app = require_once __DIR__.'/bootstrap/app.php';\n".
                "// Appka jest spłaszczona (patrz release:build-hosting) — public/ nie\n".
                "// istnieje jako osobny katalog, więc publicPath() musi wskazywać tu, a nie\n".
                "// na domyślne base_path().'/public' (inaczej Vite szuka manifestu pod\n".
                "// podwójnym '/public/build/...' i wywala ViteManifestNotFoundException).\n".
                "\$app->usePublicPath(__DIR__);\n".
                "// Prawdziwy storage appki przeniesiony na app-storage/ — public_path\n".
                "// ('storage') (== __DIR__.'/storage' teraz, że publicPath == basePath)\n".
                "// musi zostać wolny pod symlink z 'artisan storage:link', inaczej\n".
                "// koliduje z prawdziwym katalogiem storage/ i storage:link cicho pada.\n".
                "\$app->useStoragePath(__DIR__.'/app-storage');\n".
                "// Samo-naprawiające się: zwykle ten symlink zakłada 'artisan storage:link'\n".
                "// wołane raz z Instalatora, ale na już zainstalowanej appce (np. po\n".
                "// wgraniu tej poprawki na starszą instalację, gdzie ten symlink nigdy nie\n".
                "// powstał) nic więcej go nie stworzy — Instalator już się nie pokaże.\n".
                "// Tanie, więc sprawdzane na każde żądanie zamiast tylko przy instalacji.\n".
                "if (! is_link(__DIR__.'/storage') && ! is_dir(__DIR__.'/storage') && is_dir(__DIR__.'/app-storage/app/public')) {\n".
                "    @symlink(__DIR__.'/app-storage/app/public', __DIR__.'/storage');\n".
                "}\n".
                "\$app->handleRequest(Request::capture());",
            $contents
        );

        file_put_contents($path, $contents);
    }

    private function writeHtaccessFiles(string $workDir): void
    {
        $denyAll = <<<'HTACCESS'
        <IfModule mod_authz_core.c>
            Require all denied
        </IfModule>
        <IfModule !mod_authz_core.c>
            Order deny,allow
            Deny from all
        </IfModule>
        HTACCESS;

        $rootFilesPattern = self::PROTECTED_ROOT_FILES_PATTERN;
        $root = <<<HTACCESS
        <IfModule mod_rewrite.c>
            RewriteEngine On

            RewriteCond %{HTTP:Authorization} .
            RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

            RewriteCond %{HTTP:x-xsrf-token} .
            RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:x-xsrf-token}]

            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteCond %{REQUEST_URI} (.+)/\$
            RewriteRule ^ %1 [L,R=301]

            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule ^ index.php [L]
        </IfModule>

        # Appka jest w tym samym katalogu co index.php (open_basedir hostingu nie
        # pozwala trzymać jej wyżej) — te pliki w korzeniu NIE mogą być pobierane
        # wprost przez przeglądarkę (zawierają hasła do bazy / klucz appki).
        <FilesMatch "$rootFilesPattern">
        $denyAll
        </FilesMatch>
        HTACCESS;

        file_put_contents($workDir.'/.htaccess', $root);

        foreach (self::PROTECTED_DIRS as $dir) {
            if (is_dir($workDir.'/'.$dir)) {
                file_put_contents($workDir.'/'.$dir.'/.htaccess', $denyAll);
            }
        }
    }
}
