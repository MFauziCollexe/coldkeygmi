<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        ActivityLog::create([
            'table_name' => 'users',
            'record_id' => $user->id,
            'action' => 'insert',
            'new_values' => $user->getAttributes(),
            'user_id' => Auth::id() ?? 'system',
            'user_email' => Auth::user()?->email ?? 'system',
            'ip_address' => Request::ip(),
            'created_date' => now(),
            'description' => "User {$user->email} created by " . (Auth::user()?->email ?? 'system'),
        ]);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $changes = $user->getChanges();
        $original = $user->getOriginal();

        ActivityLog::create([
            'table_name' => 'users',
            'record_id' => $user->id,
            'action' => 'update',
            'old_values' => collect($original)->only(array_keys($changes))->toArray(),
            'new_values' => $changes,
            'user_id' => Auth::id() ?? 'system',
            'user_email' => Auth::user()?->email ?? 'system',
            'ip_address' => Request::ip(),
            'created_date' => now(),
            'description' => "User {$user->email} updated by " . (Auth::user()?->email ?? 'system') . '. Fields: ' . implode(', ', array_keys($changes)),
        ]);
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        ActivityLog::create([
            'table_name' => 'users',
            'record_id' => $user->id,
            'action' => 'delete',
            'old_values' => $user->getAttributes(),
            'user_id' => Auth::id() ?? 'system',
            'user_email' => Auth::user()?->email ?? 'system',
            'ip_address' => Request::ip(),
            'created_date' => now(),
            'description' => "User {$user->email} deleted by " . (Auth::user()?->email ?? 'system'),
        ]);
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        ActivityLog::create([
            'table_name' => 'users',
            'record_id' => $user->id,
            'action' => 'update',
            'new_values' => ['status' => 'active'],
            'user_id' => Auth::id() ?? 'system',
            'user_email' => Auth::user()?->email ?? 'system',
            'ip_address' => Request::ip(),
            'created_date' => now(),
            'description' => "User {$user->email} restored by " . (Auth::user()?->email ?? 'system'),
        ]);
    }
}
