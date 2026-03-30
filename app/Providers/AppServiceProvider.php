<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Employee;
use App\Observers\UserObserver;
use App\Observers\EmployeeObserver;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\ModulePermission;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers
        User::observe(UserObserver::class);
        Employee::observe(EmployeeObserver::class);
        \App\Models\Ticket::observe(\App\Observers\TicketObserver::class);

        // Share module permissions and modules list with Inertia
        Inertia::share([
            'modules' => function () {
                return config('modules');
            },
            'auth' => function () {
                $user = Auth::user();
                if (!$user) return ['user' => null, 'module_permissions' => [], 'is_admin' => false];
                $user->loadMissing([
                    'position:id,name,code,is_manager',
                    'department:id,name,code',
                ]);

                $perms = ModulePermission::where('user_id', $user->id)->pluck('module_key')->toArray();
                return [
                    'user' => $user,
                    'module_permissions' => $perms,
                    'is_admin' => $user->isAdmin(),
                ];
            },
        ]);
    }
}
