<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        $query = User::query()->with(['department', 'position']);

        // Filters
        $search = request('search');
        $status = request('status');
        $departmentId = request('department_id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('account', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $users = $query->orderBy('created_date', 'desc')->paginate(15)->withQueryString();

        // Get departments for filter
        $departments = Department::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        // Get positions for filter
        $positions = Position::where('is_active', true)->orderBy('name')->get(['id', 'name', 'department_id']);

        return Inertia::render('ControlPanel/User/UserIndex', [
            'users' => $users,
            'filters' => request()->only(['search', 'status', 'department_id', 'page']),
            'departments' => $departments,
            'positions' => $positions,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $positions = Position::where('is_active', true)->orderBy('name')->get(['id', 'name', 'department_id']);

        return Inertia::render('ControlPanel/User/UserCreate', [
            'departments' => $departments,
            'positions' => $positions,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        // Select inputs may send empty string; normalize so `nullable|exists` behaves as intended.
        $request->merge([
            'department_id' => $request->input('department_id') ?: null,
            'position_id' => $request->input('position_id') ?: null,
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account' => 'required|string|unique:users,account',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string',
            'status' => 'required|in:active,deactivated',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'is_admin' => 'nullable|boolean',
        ]);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'account' => $validated['account'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => $validated['status'],
                'department_id' => $validated['department_id'] ?? null,
                'position_id' => $validated['position_id'] ?? null,
                'is_admin' => $validated['is_admin'] ?? false,
                'user_created' => Auth::user()->email,
                'user_updated' => Auth::user()->email,
            ]);

            // Log activity
            $this->logActivity('users', $user->id, 'created', null, $user->toArray(), 'Created user: ' . $user->name);

            return redirect('/control-panel/user')->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating user', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to create user.'])->withInput();
        }
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $positions = Position::where('is_active', true)->orderBy('name')->get(['id', 'name', 'department_id']);

        return Inertia::render('ControlPanel/User/UserEdit', [
            'user' => $user,
            'departments' => $departments,
            'positions' => $positions,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        // Select inputs may send empty string; normalize so `nullable|exists` behaves as intended.
        $request->merge([
            'department_id' => $request->input('department_id') ?: null,
            'position_id' => $request->input('position_id') ?: null,
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account' => 'required|string|unique:users,account,' . $user->id,
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string',
            'status' => 'required|in:active,deactivated',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'is_admin' => 'nullable|boolean',
        ]);

        try {
            $oldData = $user->toArray();

            $updateData = [
                'name' => $validated['name'],
                'account' => $validated['account'],
                'email' => $validated['email'],
                'status' => $validated['status'],
                'department_id' => $validated['department_id'] ?? null,
                'position_id' => $validated['position_id'] ?? null,
                'is_admin' => $validated['is_admin'] ?? false,
                'user_updated' => Auth::user()->email,
            ];

            // Only update password if provided
            if ($validated['password']) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);

            // Log activity
            $this->logActivity('users', $user->id, 'updated', $oldData, $user->fresh()->toArray(), 'Updated user: ' . $user->name);

            return redirect('/control-panel/user')->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating user', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to update user.'])->withInput();
        }
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        try {
            $userName = $user->name;
            $oldData = $user->toArray();
            
            $user->delete();

            // Log activity
            $this->logActivity('users', null, 'deleted', $oldData, null, 'Deleted user: ' . $userName);

            return redirect('/control-panel/user')->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting user', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to delete user.']);
        }
    }

    /**
     * Log activity to activity_logs table.
     */
    protected function logActivity($tableName, $recordId, $action, $oldValues, $newValues, $description)
    {
        try {
            \App\Models\ActivityLog::create([
                'table_name' => $tableName,
                'record_id' => $recordId,
                'action' => $action,
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'user_id' => Auth::id(),
                'user_email' => Auth::user()->email,
                'ip_address' => request()->ip(),
                'description' => $description,
                'created_date' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error logging activity', ['error' => $e->getMessage()]);
        }
    }
}
