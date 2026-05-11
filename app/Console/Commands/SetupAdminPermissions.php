<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ModulePermission;

class SetupAdminPermissions extends Command
{
    protected $signature = 'app:setup-admin-permissions';
    protected $description = 'Setup admin user with all module permissions';

    public function handle()
    {
        $admin = User::where('email', 'admin@coldkeygmi.com')->first();

        if (!$admin) {
            $this->error('Admin user not found');
            return 1;
        }

        $admin->is_admin = true;
        $admin->save();
        $this->info('Set is_admin to true for ' . $admin->email);

        // Get all module keys from config
        $modules = config('modules');
        $allModuleKeys = $this->collectModuleKeys($modules);

        // Clear existing permissions
        ModulePermission::where('user_id', $admin->id)->delete();

        // Add all module permissions for admin
        foreach ($allModuleKeys as $key) {
            ModulePermission::create([
                'user_id' => $admin->id,
                'module_key' => $key,
            ]);
        }

        $this->info('Added ' . count($allModuleKeys) . ' module permissions for admin');
        return 0;
    }

    private function collectModuleKeys($modules)
    {
        $keys = [];
        foreach ($modules as $module) {
            // Use the configured module key directly.
            $keys[] = $module['key'];

            if (isset($module['children']) && !empty($module['children'])) {
                $keys = array_merge($keys, $this->collectModuleKeys($module['children']));
            }
        }
        return $keys;
    }
}
