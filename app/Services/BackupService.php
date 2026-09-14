<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

/**
 * Cienka nakładka na spatie/laravel-backup — trzyma tu logikę specyficzną dla
 * Craty (opcjonalne dołączenie .env, retencja z ustawień), żeby dało się to
 * wywołać programistycznie z innego miejsca w appce (np. przyszły moduł
 * Aktualizacje ma robić backup jako krok przed każdą aktualizacją), a nie
 * tylko z przycisku w UI.
 */
class BackupService
{
    public function disk(): string
    {
        return config('backup.backup.destination.disks')[0];
    }

    /** Uruchamia pełny backup (baza + storage/app/public, plus .env jeśli włączone). */
    public function run(): void
    {
        if (AppSetting::current()->backup_include_env) {
            config([
                'backup.backup.source.files.include' => array_merge(
                    config('backup.backup.source.files.include', []),
                    [base_path('.env')],
                ),
            ]);
        }

        Artisan::call('backup:run', ['--disable-notifications' => true]);
    }

    /** Usuwa kopie starsze niż retencja ustawiona przez admina (Ustawienia → Kopie zapasowe). */
    public function cleanup(): void
    {
        config(['backup.cleanup.default_strategy.keep_all_backups_for_days' => AppSetting::current()->backup_retention_days]);

        Artisan::call('backup:clean', ['--disable-notifications' => true]);
    }

    /** @return Collection<int, array{path: string, size: int, last_modified: \Illuminate\Support\Carbon}> */
    public function list(): Collection
    {
        $disk = Storage::disk($this->disk());

        return collect($disk->allFiles())
            ->filter(fn ($path) => str_ends_with($path, '.zip'))
            ->map(fn ($path) => [
                'path' => $path,
                'size' => $disk->size($path),
                'last_modified' => \Illuminate\Support\Carbon::createFromTimestamp($disk->lastModified($path)),
            ])
            ->sortByDesc('last_modified')
            ->values();
    }

    public function delete(string $path): void
    {
        Storage::disk($this->disk())->delete($this->sanitize($path));
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->disk())->exists($this->sanitize($path));
    }

    public function fullPath(string $path): string
    {
        return Storage::disk($this->disk())->path($this->sanitize($path));
    }

    /** Zip-y leżą płasko w katalogu appki (np. "graty/2026-09-14.zip") — pilnujemy, żeby nie wyjść poza dysk backupów. */
    private function sanitize(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);

        abort_if(str_contains($normalized, '..'), 404);

        return $normalized;
    }
}
