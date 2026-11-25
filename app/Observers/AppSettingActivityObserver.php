<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\AppSetting;

/** Dziennik: każda zmiana ustawień (aplikacja, moduły, poczta, powiadomienia, sprzedaż). */
class AppSettingActivityObserver
{
    public function created(AppSetting $setting): void
    {
        // Pierwszy zapis ustawień: zmiany względem wartości domyślnych.
        $defaults = (new AppSetting)->getAttributes();
        $changes = [];
        foreach ($setting->getAttributes() as $field => $value) {
            if (in_array($field, ['id', ...ActivityLog::IGNORED_FIELDS], true) || ($defaults[$field] ?? null) == $value) {
                continue;
            }
            $changes[$field] = in_array($field, [...ActivityLog::SECRET_FIELDS, ...ActivityLog::LONG_FIELDS], true) ? null : [null, $value];
        }
        if ($changes) {
            ActivityLog::record('settings.updated', null, ['changes' => $changes]);
        }
    }

    public function updated(AppSetting $setting): void
    {
        if ($changes = ActivityLog::changesOf($setting)) {
            ActivityLog::record('settings.updated', null, ['changes' => $changes]);
        }
    }
}
