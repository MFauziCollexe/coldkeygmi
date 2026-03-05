<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ModulePermission;

class DebugSession extends Command
{
    protected $signature = 'app:debug-session {email}';
    protected $description = 'Debug what would be shared to Inertia for a user';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found: $email");
            return 1;
        }

        $this->info("=== Inertia Props for: {$user->name} ===");
        $this->line('');

        // Simulate what AppServiceProvider.boot() does
        $perms = ModulePermission::where('user_id', $user->id)->pluck('module_key')->toArray();
        
        $authData = [
            'user' => $user->toArray(),
            'module_permissions' => $perms,
            'is_admin' => (bool) $user->is_admin,
        ];

        $this->line('Auth props that would be sent to frontend:');
        $this->line(json_encode($authData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->line('');
        $this->info('Summary:');
        $this->line('  User: ' . $user->email);
        $this->line('  is_admin: ' . ($user->is_admin ? 'true' : 'false'));
        $this->line('  permissions count: ' . count($perms));

        return 0;
    }
}
