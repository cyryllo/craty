<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\AppSetting;
use Illuminate\Database\Eloquent\Model;

/**
 * Dziennik: dodanie, zmiana i usunięcie danych (przedmioty, kategorie,
 * miejsca, kontakty, wypożyczenia...), tylko gdy administrator włączył
 * "zapisuj też zmiany danych". Zmiany zapisane przez saveQuietly() (np. ścieżka
 * kodu QR) nie wywołują zdarzeń i słusznie tu nie trafiają.
 */
class DataActivityObserver
{
    private function enabled(): bool
    {
        return (bool) AppSetting::current()->activity_log_all_enabled;
    }

    public function created(Model $model): void
    {
        if ($this->enabled()) {
            ActivityLog::record('data.created', $model);
        }
    }

    public function updated(Model $model): void
    {
        if ($this->enabled() && ($changes = ActivityLog::changesOf($model))) {
            ActivityLog::record('data.updated', $model, ['changes' => $changes]);
        }
    }

    public function deleted(Model $model): void
    {
        if ($this->enabled()) {
            ActivityLog::record('data.deleted', $model);
        }
    }
}
