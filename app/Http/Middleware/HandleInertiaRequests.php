<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        // In local dev with Vite HMR (`public/hot` present), asset version can churn and cause
        // Inertia to respond 409 (Conflict) to force a reload. Returning null disables the
        // version check so navigation doesn't get stuck on 409s while developing.
        if (app()->environment('local') && file_exists(public_path('hot'))) {
            return null;
        }

        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        if ($user) {
            $user->loadMissing([
                'position:id,name,code,is_manager',
                'department:id,name,code',
            ]);
        }
        
        // Get module permissions for the user
        $modulePermissions = [];
        if ($user) {
            $modulePermissions = $user->modulePermissions()->pluck('module_key')->toArray();
        }

        return [
            ...parent::share($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'auth' => [
                'user' => $user,
                'is_admin' => $user ? $user->isAdmin() : false,
                'module_permissions' => $modulePermissions,
            ],
        ];
    }
}
