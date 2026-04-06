<?php

namespace App\Console\Commands;

use App\Support\DatabaseBackupService;
use Illuminate\Console\Command;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'app:database-backup {--connection=}';
    protected $description = 'Create a database backup file in storage/app/backups/database';

    public function handle(DatabaseBackupService $backupService): int
    {
        try {
            $file = $backupService->createBackup($this->option('connection') ?: null);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Backup written to: ' . $file);

        return self::SUCCESS;
    }
}
