<?php

namespace App\Console\Commands;

use App\Services\UpdatePackageBuilder;
use App\Support\AppVersion;
use App\Support\UpdatePaths;
use Illuminate\Console\Command;

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
        {--output= : Ścieżka wynikowego .zip (domyślnie app-storage/app/releases/craty-{wersja}.zip)}';

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

        $builder->build(base_path(), $output, UpdatePaths::PACKAGE_EXCLUDES);

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
}
