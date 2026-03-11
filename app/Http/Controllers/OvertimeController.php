<?php

namespace App\Http\Controllers;

use App\Models\Overtime;
use App\Models\User;
use App\Models\Department;
use App\Models\ActivityLog;
use App\Support\DepartmentScope;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class OvertimeController extends Controller
{
    /**
     * Check if user is admin (specific email or is_admin flag)
     */
    protected function isAdmin($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }
        // Specific admin email or is_admin flag
        return $user->email === 'admin@coldkeygmi.com' || $user->isAdmin();
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
                return DepartmentScope::expandManagedDepartmentIds([(int) $position->department_id]);
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

        $query = Overtime::query()
            ->with(['user', 'user.department', 'reviewer'])
            ->whereIn('user_id', $visibleUserIds);

        // Filters
        $search = request('search');
        $status = request('status');
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

        if ($startDate) {
            $query->where('overtime_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('overtime_date', '<=', $endDate);
        }

        $overtimes = $query->orderBy('overtime_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Get departments for filter (only visible ones)
        $departments = Department::whereIn('id', $visibleDeptIds)
            ->select('id', 'name')
            ->get();

        return Inertia::render('GMIHR/Overtime/Index', [
            'overtimes' => $overtimes,
            'filters' => request()->only(['search', 'status', 'start_date', 'end_date', 'page']),
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
        return Inertia::render('GMIHR/Overtime/Create');
    }

    /**
     * Display the specified resource.
     */
    public function show(Overtime $overtime)
    {
        $userId = Auth::id();
        
        // Load the overtime with related data
        $overtime->load(['user', 'user.department', 'reviewer']);

        return Inertia::render('GMIHR/Overtime/Show', [
            'overtime' => $overtime,
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
            'overtime_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'reason' => 'required|string|min:5',
        ]);

        $data['user_id'] = Auth::id();
        $data['hours'] = Overtime::calculateHours($data['start_time'], $data['end_time']);
        $data['status'] = 'pending';

        $overtime = Overtime::create($data);

        // Activity Log for Create
        $this->logActivity(
            'overtimes',
            $overtime->id,
            'created',
            null,
            $data,
            'Created overtime request for ' . $data['overtime_date']
        );

        return redirect()->route('overtime.index')->with('success', 'Permintaan overtime berhasil diajukan. Menunggu persetujuan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Overtime $overtime)
    {
        $userId = Auth::id();
        if (!$userId) {
            abort(403);
        }

        $overtime->loadMissing(['user:id,department_id']);
        $targetDeptId = (int) optional($overtime->user)->department_id;

        // Only admin or manager of the requester's department can approve/reject.
        if (!$this->isAdmin($userId)) {
            if (!$this->isManager($userId)) {
                abort(403, 'Anda tidak memiliki izin untuk menyetujui/menolak overtime ini.');
            }

            $visibleDeptIds = $this->getVisibleDepartmentIds($userId);
            if ($targetDeptId <= 0 || !in_array($targetDeptId, $visibleDeptIds, true)) {
                abort(403, 'Anda tidak memiliki izin untuk menyetujui/menolak overtime departemen ini.');
            }
        }

        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
            'review_notes' => 'nullable|string',
        ]);

        $oldData = $overtime->toArray();
        $data['reviewed_by'] = Auth::id();
        $data['reviewed_at'] = now();

        $overtime->update($data);

        // Activity Log for Update (approve/reject)
        $action = $data['status'] === 'approved' ? 'approved' : 'rejected';
        $this->logActivity(
            'overtimes',
            $overtime->id,
            $action,
            $oldData,
            $overtime->toArray(),
            ucfirst($action) . ' overtime request'
        );

        $message = $data['status'] === 'approved' 
            ? 'Permintaan overtime telah disetujui.' 
            : 'Permintaan overtime telah ditolak.';

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
