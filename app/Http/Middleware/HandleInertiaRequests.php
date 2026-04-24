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

        return [
            ...parent::share($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'auth' => [
                'user' => fn () => $this->sharedUser($user),
                'is_admin' => fn () => $user ? $user->isAdmin() : false,
                'module_permissions' => fn () => $user
                    ? $user->modulePermissions()->pluck('module_key')->toArray()
                    : [],
            ],
        ];
    }

    protected function sharedUser($user): ?array
    {
        if (!$user) {
            return null;
        }

        $user->loadMissing([
            'position:id,name,code,is_manager',
            'department:id,name,code',
        ]);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => $user->first_name ?? null,
            'last_name' => $user->last_name ?? null,
            'email' => $user->email,
            'position' => $user->position ? [
                'id' => $user->position->id,
                'name' => $user->position->name,
                'code' => $user->position->code,
                'is_manager' => (bool) $user->position->is_manager,
            ] : null,
            'department' => $user->department ? [
                'id' => $user->department->id,
                'name' => $user->department->name,
                'code' => $user->department->code,
            ] : null,
        ];
    }
}
