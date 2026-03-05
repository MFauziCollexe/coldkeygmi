<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureModulePermission
{
    /**
     * Handle an incoming request.
     * Usage: ->middleware('module.access:utility.tickets')
     */
    public function handle(Request $request, Closure $next, $moduleKey = null)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        // if no specific module key provided, allow
        if (!$moduleKey) return $next($request);

        if ($user->isAdmin() || $user->hasModulePermission($moduleKey)) {
            return $next($request);
        }

        abort(403);
    }
}
