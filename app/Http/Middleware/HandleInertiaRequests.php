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
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        
        // Get module permissions for the user
        $modulePermissions = [];
        if ($user) {
            $modulePermissions = $user->modulePermissions()->pluck('module_key')->toArray();
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'is_admin' => $user ? $user->isAdmin() : false,
                'module_permissions' => $modulePermissions,
            ],
        ];
    }
}
