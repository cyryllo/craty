<?php

namespace App\Console\Commands;

use App\Exceptions\BackupException;
use App\Services\BackupService;
use Illuminate\Console\Command;

/**
 * backup:run          kopia od razu
 * backup:run --auto   kopia automatyczna, tylko gdy włączona i przyszła na nią pora
 *                     (uruchamiane codziennie przez harmonogram, patrz routes/console.php)
 */
class RunBackup extends Command
{
    protected $signature = 'backup:run {--auto : Only when automatic backups are on and one is due}';

    protected $description = 'Create a backup of the database and uploaded files';

    public function handle(BackupService $backups): int
    {
        if ($this->option('auto') && ! $backups->automaticDue()) {
            return self::SUCCESS;
        }

        try {
            $this->info($backups->create($this->option('auto') ? 'auto' : 'manual'));
        } catch (BackupException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
