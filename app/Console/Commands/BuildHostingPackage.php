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

    /** Katalogi z kodem/danymi appki — po spłaszczeniu dostają .htaccess blokujący dostęp z przeglądarki. */
    private const PROTECTED_DIRS = ['app', 'bootstrap', 'config', 'database', 'lang', 'resources', 'routes', 'storage', 'vendor'];

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
            $this->rewriteIndexPhp($workDir.'/index.php');
            $this->writeHtaccessFiles($workDir);

            // Już spłaszczone i tak samo wyczyszczone jak źródło — zero
            // dodatkowych wykluczeń przy pakowaniu z powrotem.
            $builder->build($workDir, $output, []);

            $this->info('Gotowe: '.$output);
            $this->line('SHA-256: '.hash_file('sha256', $output));

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

        $contents = str_replace(
            "(require_once __DIR__.'/bootstrap/app.php')\n    ->handleRequest(Request::capture());",
            "\$app = require_once __DIR__.'/bootstrap/app.php';\n".
                "// Appka jest spłaszczona (patrz release:build-hosting) — public/ nie\n".
                "// istnieje jako osobny katalog, więc publicPath() musi wskazywać tu, a nie\n".
                "// na domyślne base_path().'/public' (inaczej Vite szuka manifestu pod\n".
                "// podwójnym '/public/build/...' i wywala ViteManifestNotFoundException).\n".
                "\$app->usePublicPath(__DIR__);\n".
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
