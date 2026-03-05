<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ModulePermission;

class DebugUserPermissions extends Command
{
    protected $signature = 'app:debug-user {email}';
    protected $description = 'Debug user permissions and check sidebar config match';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found: $email");
            return 1;
        }

        $this->info("=== User: {$user->name} ({$email}) ===");
        $this->line('');

        $this->line('Database Permissions:');
        $permissions = ModulePermission::where('user_id', $user->id)->pluck('module_key')->toArray();
        $this->line('  Count: ' . count($permissions));
        foreach ($permissions as $perm) {
            $this->line('    - ' . $perm);
        }

        $this->line('');

        // Check sidebar config
        $this->line('Sidebar Config Module Keys:');
        $modules = config('modules');
        $configKeys = $this->collectKeys($modules);
        $this->line('  Count: ' . count($configKeys));
        foreach ($configKeys as $key) {
            $status = in_array($key, $permissions) ? '✓' : '✗';
            $this->line('    ' . $status . ' ' . $key);
        }

        $this->line('');

        // Check matches
        $missing = array_diff($configKeys, $permissions);
        $extra = array_diff($permissions, $configKeys);

        if (!empty($missing)) {
            $this->warn('Missing in DB (not accessible):');
            foreach ($missing as $key) {
                $this->line('  - ' . $key);
            }
            $this->line('');
        }

        if (!empty($extra)) {
            $this->warn('Extra in DB (not in config):');
            foreach ($extra as $key) {
                $this->line('  - ' . $key);
            }
            $this->line('');
        }

        if (empty($missing) && empty($extra)) {
            $this->info('✓ Perfect match between DB and config!');
        }

        return 0;
    }

    private function collectKeys($modules)
    {
        $keys = [];
        foreach ($modules as $module) {
            // Use key directly - config keys are already complete
            $keys[] = $module['key'];

            if (isset($module['children']) && !empty($module['children'])) {
                $keys = array_merge($keys, $this->collectKeys($module['children']));
            }
        }
        return $keys;
    }
}
