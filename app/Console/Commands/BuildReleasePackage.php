<?php

namespace App\Console\Commands;

use App\Services\UpdatePackageBuilder;
use App\Support\AppVersion;
use App\Support\UpdatePaths;
use Illuminate\Console\Command;

/**
 * Deweloperska strona Modułu Aktualizacje — buduje samowystarczalną paczkę
 * .zip (kod + vendor/ + skompilowane assety, patrz UpdatePaths::PACKAGE_EXCLUDES)
 * do wgrania przez administratora w Ustawienia → Aktualizacje. Uruchamiana w
 * tym repo przy każdym wydaniu, nie na docelowym hostingu.
 */
class BuildReleasePackage extends Command
{
    protected $signature = 'release:build
        {version? : Docelowa wersja (domyślnie ta już zapisana w pliku VERSION)}
        {--min-version= : Minimalna wersja appki, z której można zastosować tę paczkę}
        {--changelog=* : Linia changeloga do manifestu — opcja powtarzalna}
        {--output= : Ścieżka wynikowego .zip (domyślnie storage/app/releases/craty-{wersja}-update.zip)}';

    protected $description = 'Buduje paczkę .zip aktualizacji do wgrania przez panel administratora.';

    public function handle(UpdatePackageBuilder $builder, AppVersion $appVersion): int
    {
        $version = $this->argument('version');

        if ($version) {
            $appVersion->set($version);
        } else {
            $version = $appVersion->current();
        }

        // "-update" w nazwie odróżnia paczkę do wgrania przez panel (Ustawienia
        // → Aktualizacje) od "-full" budowanej ręcznie z --output pod świeżą
        // instalację na hostingu — treść obu jest dziś identyczna (ta sama
        // migawka całej appki), to czysto etykieta dla admina, który plik do
        // czego wgrać.
        $output = $this->option('output') ?: storage_path("app/releases/craty-{$version}-update.zip");

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

        $builder->build(base_path(), $output, UpdatePaths::PACKAGE_EXCLUDES);

        // Manifest dokładamy osobno, już do gotowego archiwum — to jedyny
        // plik paczki, który nie pochodzi wprost z drzewa repo.
        $zip = new \ZipArchive();
        $zip->open($output);
        $zip->addFromString('update-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->close();

        $this->info('Gotowe: '.$output);
        $this->line('SHA-256: '.hash_file('sha256', $output));

        return self::SUCCESS;
    }
}
