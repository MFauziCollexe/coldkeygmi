<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ModulePermission;

class ModuleControlController extends Controller
{
    public function index()
    {
        // load all users (id, name, email)
        $users = \App\Models\User::select('id','name','email')->get();

        // modules structure from config file (js will also import same)
        $modules = config('modules') ?? [];

        return Inertia::render('ControlPanel/ModuleControl', [
            'users' => $users,
            'modules' => $modules,
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $userId = $data['user_id'];

        // normalize permissions array to avoid duplicate inserts
        $perms = $this->normalizeModuleKeys($data['permissions'] ?? []);

        // remove existing for user
        ModulePermission::where('user_id', $userId)->delete();

        // insert new
        foreach ($perms as $key) {
            ModulePermission::create(['user_id' => $userId, 'module_key' => $key]);
        }

        return redirect()->back()->with('success', 'Permissions updated');
    }

    // optional endpoint to fetch user's permissions for UI
    public function forUser($userId)
    {
        $perms = ModulePermission::where('user_id', $userId)->pluck('module_key')->toArray();
        return response()->json(['permissions' => $perms]);
    }

    protected function normalizeModuleKeys(array $keys): array
    {
        return collect($keys)
            ->filter(fn($key) => is_string($key))
            ->map(fn($key) => trim($key))
            ->filter(fn($key) => $key !== '')
            ->unique()
            ->values()
            ->all();
    }
}
