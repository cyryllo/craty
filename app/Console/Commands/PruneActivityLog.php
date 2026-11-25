<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

/** Usuwa wpisy dziennika aktywności starsze niż czas z ustawień (ActivityLog::retentionDays(), 0 = nic). */
class PruneActivityLog extends Command
{
    protected $signature = 'activity:prune';

    protected $description = 'Delete activity log entries older than the retention period';

    public function handle(): int
    {
        $this->info(__(':count old entries deleted.', ['count' => ActivityLog::prune()]));

        return self::SUCCESS;
    }
}
