<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ModulePermission;
use Illuminate\Support\Storage;

class BackupModulePermissions extends Command
{
    protected $signature = 'app:backup-module-permissions';
    protected $description = 'Backup module_permissions table to SQL file in storage/backups';

    public function handle()
    {
        $rows = ModulePermission::all();

        if ($rows->isEmpty()) {
            $this->info('No module_permissions rows to backup.');
            return 0;
        }

        $dir = storage_path('backups');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ts = date('Ymd_His');
        $file = $dir . DIRECTORY_SEPARATOR . "module_permissions_backup_{$ts}.sql";

        $sql = "-- Backup of module_permissions table\n";
        $sql .= "-- Generated: " . date('c') . "\n\n";
        $sql .= "DELETE FROM module_permissions;\n\n";

        foreach ($rows as $r) {
            $userId = (int)$r->user_id;
            $key = addslashes($r->module_key);
            $created = $r->created_at ? $r->created_at->toDateTimeString() : date('Y-m-d H:i:s');
            $updated = $r->updated_at ? $r->updated_at->toDateTimeString() : date('Y-m-d H:i:s');
            $sql .= "INSERT INTO module_permissions (id, user_id, module_key, created_at, updated_at) VALUES ({$r->id}, {$userId}, '{$key}', '{$created}', '{$updated}');\n";
        }

        file_put_contents($file, $sql);

        $this->info('Backup written to: ' . $file);
        return 0;
    }
}
