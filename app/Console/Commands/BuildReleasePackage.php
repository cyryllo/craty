<?php

namespace App\Console\Commands;

use App\Services\UpdatePackageBuilder;
use App\Support\AppVersion;
use App\Support\UpdatePaths;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Deweloperska strona Modułu Aktualizacje — buduje samowystarczalną paczkę
 * .zip (kod + vendor/ + skompilowane assety, patrz UpdatePaths::PACKAGE_EXCLUDES)
 * do wgrania przez administratora w Ustawienia → Aktualizacje, ALBO do
 * ręcznego rozpakowania wprost do document rootu na świeżej instalacji —
 * appka ma dziś jeden, stały, spłaszczony układ (patrz CLAUDE.md "Project
 * layout"), więc jedna paczka wystarcza na oba przypadki (wcześniej
 * istniała osobna komenda `release:build-hosting` robiąca spłaszczenie w
 * locie — zbędna teraz, gdy repo samo jest już spłaszczone). Uruchamiana w
 * tym repo przy każdym wydaniu, nie na docelowym hostingu.
 */
class BuildReleasePackage extends Command
{
    protected $signature = 'release:build
        {version? : Docelowa wersja (domyślnie ta już zapisana w pliku VERSION)}
        {--min-version= : Minimalna wersja appki, z której można zastosować tę paczkę}
        {--changelog=* : Linia changeloga do manifestu — opcja powtarzalna}
        {--output= : Ścieżka wynikowego .zip (domyślnie app-storage/app/releases/craty-{wersja}.zip)}
        {--with-dev : Zostaw w vendor/ narzędzia programistyczne (bez composer install --no-dev, większa paczka)}';

    protected $description = 'Buduje paczkę .zip aktualizacji/instalacji do wgrania przez panel administratora albo ręcznego rozpakowania na hostingu.';

    public function handle(UpdatePackageBuilder $builder, AppVersion $appVersion): int
    {
        $version = $this->argument('version');

        if ($version) {
            $appVersion->set($version);
        } else {
            $version = $appVersion->current();
        }

        $output = $this->option('output') ?: storage_path("app/releases/craty-{$version}.zip");

        $manifest = [
            'version' => $version,
            'min_version' => $this->option('min-version') ?: null,
            'built_at' => now()->toIso8601String(),
            'changelog' => $this->option('changelog'),
            // Informacyjne — apply() i tak polega na `migrate --force`,
            // które samo wykryje, co jeszcze nie zostało uruchomione.
            'migrations' => collect(glob(database_path('migrations/*.php')))
                ->map(fn ($path) => basename($path))
                ->values()
                ->all(),
        ];

        $this->info("Buduję paczkę {$version}...");

        // Domyślnie paczka powstaje z kopii repo, w której composer usuwa z vendor/
        // narzędzia programistyczne (faker, phpunit, pint, laravel-lang...) i od
        // nowa generuje autoloader oraz bootstrap/cache/packages.php: na
        // serwerze lista pakietów musi pasować do vendor/ z paczki.
        $source = $this->option('with-dev') ? base_path() : $this->stageWithoutDevDependencies($builder);

        try {
            $builder->build($source, $output, UpdatePaths::PACKAGE_EXCLUDES);
        } finally {
            if ($source !== base_path()) {
                File::deleteDirectory($source);
            }
        }

        // Manifest dokładamy osobno, już do gotowego archiwum — to jedyny
        // plik paczki, który nie pochodzi wprost z drzewa repo.
        $zip = new \ZipArchive();
        $zip->open($output);
        $zip->addFromString('update-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->close();

        $hash = $builder->writeChecksumFile($output);

        $this->info('Gotowe: '.$output);
        $this->line('SHA-256: '.$hash.' (zapisany też obok jako '.basename($output).'.sha256)');

        return self::SUCCESS;
    }

    private function stageWithoutDevDependencies(UpdatePackageBuilder $builder): string
    {
        // Poza repo: katalog wewnątrz app-storage skopiowałby sam siebie.
        $stage = sys_get_temp_dir().'/craty-release-'.bin2hex(random_bytes(4));
        $this->line('Kopiuję pliki do '.$stage.'...');
        $builder->copyTree(base_path(), $stage, UpdatePaths::PACKAGE_EXCLUDES);
        // composer.lock jest wykluczony z paczki, ale tutaj musi być: bez niego
        // composer liczyłby wersje od nowa (inne niż przetestowane) i pobierał je z sieci.
        File::copy(base_path('composer.lock'), $stage.'/composer.lock');
        // Skrypty composera (package:discover) potrzebują tych katalogów.
        foreach (['bootstrap/cache', 'app-storage/framework/cache', 'app-storage/framework/views', 'app-storage/framework/sessions', 'app-storage/logs'] as $dir) {
            File::ensureDirectoryExists($stage.'/'.$dir);
        }

        $this->line('composer install --no-dev...');
        $result = Process::path($stage)
            ->timeout(900)
            ->env(['COMPOSER_HOME' => $stage.'/.composer-home', 'COMPOSER_NO_INTERACTION' => '1'])
            ->run(['composer', 'install', '--no-dev', '--optimize-autoloader', '--no-interaction', '--no-progress']);

        File::deleteDirectory($stage.'/.composer-home');
        if (! $result->successful()) {
            File::deleteDirectory($stage);
            throw new \RuntimeException("composer install --no-dev nie powiódł się (spróbuj --with-dev):\n".$result->errorOutput());
        }

        return $stage;
    }
}
