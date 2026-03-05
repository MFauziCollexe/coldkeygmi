<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ListUsers extends Command
{
    protected $signature = 'app:list-users';
    protected $description = 'List all users with permission info';

    public function handle()
    {
        $this->line('=== Users List ===');
        $this->line('');

        $users = User::select('id', 'name', 'email', 'is_admin')->get();

        foreach ($users as $user) {
            $admin = $user->is_admin ? '✓ ADMIN' : '';
            $perms = $user->modulePermissions()->count();
            $this->line(sprintf(
                '%d | %s | %s | %s | Perms: %d',
                $user->id,
                $user->name,
                $user->email,
                $admin,
                $perms
            ));
        }

        return 0;
    }
}
