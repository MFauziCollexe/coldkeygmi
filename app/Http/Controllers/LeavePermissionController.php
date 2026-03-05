<?php

namespace App\Http\Controllers;

use App\Models\LeavePermission;
use App\Models\User;
use App\Models\Department;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class LeavePermissionController extends Controller
{
    /**
     * Check if user is admin
     */
    protected function isAdmin($userId)
    {
        $user = User::find($userId);
        return $user && $user->isAdmin();
    }

    /**
     * Check if user is a manager
     */
    protected function isManager($userId)
    {
        $user = User::find($userId);
        if (!$user || !$user->position_id) {
            return false;
        }

        $position = \App\Models\Position::find($user->position_id);
        return $position && $position->is_manager;
    }

    /**
     * Get department IDs that user can view
     * - Admin: all departments
     * - Manager: their managed department
     * - Regular user: their own department only
     */
    protected function getVisibleDepartmentIds($userId)
    {
        $user = User::find($userId);
        
        if (!$user) {
            return [];
        }

        // Admin can see all
        if ($this->isAdmin($userId)) {
            return Department::pluck('id')->toArray();
        }

        // Manager can see their department
        if ($this->isManager($userId)) {
            $position = \App\Models\Position::find($user->position_id);
            if ($position && $position->department_id) {
                return [$position->department_id];
            }
        }

        // Regular user can only see their own department
        if ($user->department_id) {
            return [$user->department_id];
        }

        return [];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = Auth::id();
        
        // Get visible department IDs
        $visibleDeptIds = $this->getVisibleDepartmentIds($userId);
        
        // Get users in visible departments
        $visibleUserIds = User::whereIn('department_id', $visibleDeptIds)->pluck('id')->toArray();

        $query = LeavePermission::query()
            ->with(['user', 'user.department', 'reviewer'])
            ->whereIn('user_id', $visibleUserIds);

        // Filters
        $search = request('search');
        $status = request('status');
        $type = request('type');
        $startDate = request('start_date');
        $endDate = request('end_date');

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter status - only apply if status is not empty
        if (!empty($status)) {
            $query->where('status', $status);
        }

        // Filter type (tab) - only apply if type is not empty
        if (!empty($type)) {
            $query->where('type', $type);
        }

        if ($startDate) {
            $query->where('start_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('end_date', '<=', $endDate);
        }

        $leavePermissions = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Get departments for filter (only visible ones)
        $departments = Department::whereIn('id', $visibleDeptIds)
            ->select('id', 'name')
            ->get();

        return Inertia::render('GMIHR/LeavePermission/Index', [
            'leavePermissions' => $leavePermissions,
            'filters' => request()->only(['search', 'status', 'type', 'start_date', 'end_date', 'page']),
            'departments' => $departments,
            'isAdmin' => $this->isAdmin($userId),
            'isManager' => $this->isManager($userId),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('GMIHR/LeavePermission/Create');
    }

    /**
     * Display the specified resource.
     */
    public function show(LeavePermission $leavePermission)
    {
        $userId = Auth::id();
        
        // Load the leave permission with related data
        $leavePermission->load(['user', 'user.department', 'reviewer']);

        return Inertia::render('GMIHR/LeavePermission/Show', [
            'leavePermission' => $leavePermission,
            'isAdmin' => $this->isAdmin($userId),
            'isManager' => $this->isManager($userId),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:cuti,izin,sakit,dinas_luar',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:5',
        ]);

        $data['user_id'] = Auth::id();
        $data['days'] = LeavePermission::calculateDays($data['start_date'], $data['end_date']);
        $data['status'] = 'pending';

        $leavePermission = LeavePermission::create($data);

        // Activity Log for Create
        $this->logActivity(
            'leave_permissions',
            $leavePermission->id,
            'created',
            null,
            $data,
            'Created leave permission request: ' . $data['type']
        );

        return redirect()->route('leave-permission.index')->with('success', 'Permintaan berhasil diajukan. Menunggu persetujuan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeavePermission $leavePermission)
    {
        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
            'review_notes' => 'nullable|string',
        ]);

        $oldData = $leavePermission->toArray();
        $data['reviewed_by'] = Auth::id();
        $data['reviewed_at'] = now();

        $leavePermission->update($data);

        // Activity Log for Update (approve/reject)
        $action = $data['status'] === 'approved' ? 'approved' : 'rejected';
        $this->logActivity(
            'leave_permissions',
            $leavePermission->id,
            $action,
            $oldData,
            $leavePermission->toArray(),
            ucfirst($action) . ' leave permission request'
        );

        $message = $data['status'] === 'approved' 
            ? 'Permintaan telah disetujui.' 
            : 'Permintaan telah ditolak.';

        return redirect()->back()->with('success', $message);
    }

    /**
     * Helper function to log activity
     */
    private function logActivity($tableName, $recordId, $action, $oldValues, $newValues, $description)
    {
        $user = Auth::user();
        
        ActivityLog::create([
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'ip_address' => request()->ip(),
            'created_date' => now(),
            'description' => $description,
        ]);
    }
}
