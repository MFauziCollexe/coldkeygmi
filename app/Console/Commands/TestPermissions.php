<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class TestPermissions extends Command
{
    protected $signature = 'app:test-permissions';
    protected $description = 'Test permission system setup';

    public function handle()
    {
        $this->line('=== Testing Permission System ===');
        $this->line('');

        // Check admin
        $admin = User::where('email', 'admin@coldkeygmi.com')->first();
        if ($admin) {
            $this->info('✓ Admin User Found:');
            $this->line('  Email: ' . $admin->email);
            $this->line('  is_admin: ' . ($admin->is_admin ? 'YES ✓' : 'NO ✗'));
            $perms = $admin->modulePermissions()->count();
            $this->line('  Permissions: ' . $perms . ' modules');
            if ($perms > 0) {
                $keys = $admin->modulePermissions()->pluck('module_key')->toArray();
                $this->line('  Modules: ' . implode(', ', array_slice($keys, 0, 3)) . (count($keys) > 3 ? ', ...' : ''));
            }
        } else {
            $this->error('✗ Admin user not found');
        }

        $this->line('');

        // Check Fauzi - using name field
        $fauzi = User::where('email', 'fauzi.muhammad@gmail.com')
            ->orWhere(function ($q) {
                $q->where('name', 'like', '%Fauzi%');
            })
            ->first();

        if ($fauzi) {
            $this->info('✓ Fauzi User Found:');
            $this->line('  Email: ' . $fauzi->email);
            $this->line('  is_admin: ' . ($fauzi->is_admin ? 'YES' : 'NO'));
            $perms = $fauzi->modulePermissions()->count();
            $this->line('  Permissions: ' . $perms . ' modules');
            if ($perms > 0) {
                $keys = $fauzi->modulePermissions()->pluck('module_key')->toArray();
                foreach ($keys as $key) {
                    $this->line('    - ' . $key);
                }
            }
        } else {
            $this->warn('⚠ Fauzi user not found - check ModuleControl to grant permissions');
        }

        $this->line('');
        $this->info('Test complete!');
        return 0;
    }
}
