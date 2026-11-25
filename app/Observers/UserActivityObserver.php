<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\User;

/** Dziennik: zakładanie, zmiany (rola, aktywność, e-mail, hasło) i usuwanie kont. */
class UserActivityObserver
{
    public function created(User $user): void
    {
        ActivityLog::record('user.created', $user, ['changes' => ['role' => [null, $user->role]]]);
    }

    public function updated(User $user): void
    {
        $changes = ActivityLog::changesOf($user);
        if (! $changes) {
            return; // np. sam remember_token przy logowaniu
        }
        // Reset hasła z linku w e-mailu zapisuje osobne zdarzenie (auth.password_reset).
        if (auth()->guest() && ! array_diff(array_keys($changes), ['password'])) {
            return;
        }

        ActivityLog::record('user.updated', $user, ['changes' => $changes]);
    }

    public function deleted(User $user): void
    {
        ActivityLog::record('user.deleted', $user);
    }
}
