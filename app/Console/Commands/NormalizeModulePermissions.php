<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ModulePermission;
use Illuminate\Support\Facades\DB;

class NormalizeModulePermissions extends Command
{
    protected $signature = 'app:normalize-module-permissions {--apply : Actually perform the normalization (default is dry-run)}';
    protected $description = 'Normalize module_permission.module_key from dot.format to underscore_format (dry-run by default)';

    public function handle()
    {
        $apply = $this->option('apply');

        $this->info('Scanning module_permissions for dot-form keys...');

        $query = ModulePermission::where('module_key', 'like', '%.%');
        $total = $query->count();

        if ($total === 0) {
            $this->info('No records found that require normalization.');
            return 0;
        }

        $this->info("Found {$total} records with dot-form keys. Preparing plan...");

        $samples = $query->limit(20)->get();
        $this->table(
            ['id', 'user_id', 'old_key', 'new_key'],
            $samples->map(function ($r) {
                return ['id' => $r->id, 'user_id' => $r->user_id, 'old_key' => $r->module_key, 'new_key' => str_replace('.', '_', $r->module_key)];
            })->toArray()
        );

        // Additional stats: how many target keys already exist per user
        $conflicts = 0;
        $query->chunk(100, function ($rows) use (&$conflicts) {
            foreach ($rows as $r) {
                $newKey = str_replace('.', '_', $r->module_key);
                $exists = ModulePermission::where('user_id', $r->user_id)->where('module_key', $newKey)->exists();
                if ($exists) $conflicts++;
            }
        });

        $this->info("Planned changes: {$total} entries. Potential conflicts (duplicates) if applied: {$conflicts}.");

        if (!$apply) {
            $this->info('Dry-run only. To apply changes run: php artisan app:normalize-module-permissions --apply');
            return 0;
        }

        if (!$this->confirm('Proceed with applying the normalization now? This will modify records.')) {
            $this->info('Aborted by user. No changes made.');
            return 1;
        }

        $this->info('Applying normalization...');

        $processed = 0;
        $updated = 0;
        $deleted = 0;

        DB::beginTransaction();
        try {
            $query->orderBy('id')->chunk(100, function ($rows) use (&$processed, &$updated, &$deleted) {
                foreach ($rows as $r) {
                    $processed++;
                    $newKey = str_replace('.', '_', $r->module_key);

                    $exists = ModulePermission::where('user_id', $r->user_id)->where('module_key', $newKey)->first();
                    if ($exists) {
                        // target already exists, remove duplicate (old row)
                        ModulePermission::where('id', $r->id)->delete();
                        $deleted++;
                    } else {
                        // update key
                        ModulePermission::where('id', $r->id)->update(['module_key' => $newKey]);
                        $updated++;
                    }
                }
            });

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error during normalization: ' . $e->getMessage());
            return 1;
        }

        $this->info("Done. Processed: {$processed}. Updated: {$updated}. Removed duplicates: {$deleted}.");
        return 0;
    }
}
