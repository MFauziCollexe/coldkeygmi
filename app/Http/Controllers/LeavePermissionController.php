<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\Employee;
use App\Models\LeavePermission;
use App\Models\User;
use App\Models\Department;
use App\Models\ActivityLog;
use App\Support\DepartmentScope;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LeavePermissionController extends Controller
{
    use RemembersIndexUrl;

    protected function getActorEmployee($userId): ?Employee
    {
        if (!$userId) {
            return null;
        }

        return Employee::query()
            ->select('id', 'user_id', 'department_id', 'position_id', 'name', 'nik')
            ->where('user_id', $userId)
            ->first();
    }

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
        if (!$user) {
            return false;
        }

        $positionId = (int) ($user->position_id ?? 0);
        if ($positionId <= 0) {
            $emp = $this->getActorEmployee($userId);
            $positionId = (int) ($emp?->position_id ?? 0);
        }

        if ($positionId <= 0) {
            return false;
        }

        $position = \App\Models\Position::find($positionId);
        return $position && $position->is_manager;
    }

    /**
     * Check if user belongs to IT department.
     */
    protected function isItUser($userId): bool
    {
        if (!$userId) {
            return false;
        }

        $user = User::query()
            ->with('department:id,code,name')
            ->find($userId);

        $department = $user?->department;

        if (!$department) {
            $employeeDepartmentId = (int) (Employee::where('user_id', $userId)->value('department_id') ?? 0);
            if ($employeeDepartmentId > 0) {
                $department = Department::query()
                    ->select('id', 'code', 'name')
                    ->find($employeeDepartmentId);
            }
        }

        if (!$department) {
            return false;
        }

        $code = strtoupper(trim((string) ($department->code ?? '')));
        $name = strtoupper(trim((string) ($department->name ?? '')));

        if ($code === 'IT') {
            return true;
        }

        if (str_contains($name, 'INFORMATION TECHNOLOGY')) {
            return true;
        }

        return preg_match('/\bIT\b/', $name) === 1;
    }

    /**
     * Check if user is a supervisor.
     *
     * This project currently doesn't have an explicit `is_supervisor` flag on positions,
     * so we infer it from position `code` / `name` containing "SPV" / "Supervisor".
     */
    protected function isSupervisor($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        $positionId = (int) ($user->position_id ?? 0);
        if ($positionId <= 0) {
            $emp = $this->getActorEmployee($userId);
            $positionId = (int) ($emp?->position_id ?? 0);
        }

        if ($positionId <= 0) {
            return false;
        }

        $position = \App\Models\Position::find($positionId);
        if (!$position) {
            return false;
        }

        $code = strtoupper(trim((string) ($position->code ?? '')));
        $name = strtoupper(trim((string) ($position->name ?? '')));

        if ($code === 'SPV' || str_ends_with($code, '-SPV') || str_contains($code, 'SPV')) {
            return true;
        }

        return str_contains($name, 'SUPERVISOR') || str_contains($name, 'SPV');
    }

    /**
     * Get department IDs that user can view
     * - Admin: all departments
     * - IT: all departments
     * - Manager: their managed department
     * - Regular user: their own department only
     */
    protected function getVisibleDepartmentIds($userId)
    {
        $user = User::find($userId);
        
        if (!$user) {
            return [];
        }

        $emp = $this->getActorEmployee($userId);

        // Admin can see all
        if ($this->isAdmin($userId)) {
            return Department::pluck('id')->toArray();
        }

        // IT can see all
        if ($this->isItUser($userId)) {
            return Department::pluck('id')->toArray();
        }

        // Manager can see their department
        if ($this->isManager($userId)) {
            $positionId = (int) ($user->position_id ?? 0);
            if ($positionId <= 0) {
                $positionId = (int) ($emp?->position_id ?? 0);
            }

            $position = $positionId > 0 ? \App\Models\Position::find($positionId) : null;
            if ($position && $position->department_id) {
                return DepartmentScope::expandManagedDepartmentIds([(int) $position->department_id]);
            }
        }

        // Regular user can only see their own department
        $deptId = (int) ($user->department_id ?? 0);
        if ($deptId <= 0) {
            $deptId = (int) ($emp?->department_id ?? 0);
        }
        if ($deptId > 0) {
            return [$deptId];
        }

        return [];
    }

    /**
     * Check if a user can review (approve/reject) a leave permission.
     * - Admin: can review all
     * - Manager: only requests from visible departments
     */
    protected function canReviewLeavePermission($userId, LeavePermission $leavePermission): bool
    {
        if ($this->isAdmin($userId)) {
            return true;
        }

        if (!$this->isManager($userId)) {
            return false;
        }

        $targetDeptId = $this->resolveLeavePermissionDepartmentId($leavePermission);
        if ($targetDeptId <= 0) {
            return false;
        }

        $visibleDeptIds = $this->getVisibleDepartmentIds($userId);
        return in_array($targetDeptId, $visibleDeptIds, true);
    }

    protected function resolveLeavePermissionDepartmentId(LeavePermission $leavePermission): int
    {
        $targetDeptId = (int) optional($leavePermission->employee)->department_id;
        if ($targetDeptId <= 0) {
            $targetDeptId = (int) optional($leavePermission->user)->department_id;
        }
        if ($targetDeptId <= 0 && $leavePermission->user_id) {
            $targetDeptId = (int) Employee::where('user_id', (int) $leavePermission->user_id)->value('department_id');
        }

        return $targetDeptId;
    }

    protected function canAccessLeavePermission($userId, LeavePermission $leavePermission): bool
    {
        if ($this->isAdmin($userId)) {
            return true;
        }

        $targetDeptId = $this->resolveLeavePermissionDepartmentId($leavePermission);
        if ($targetDeptId <= 0) {
            return false;
        }

        $visibleDeptIds = $this->getVisibleDepartmentIds($userId);
        return in_array($targetDeptId, $visibleDeptIds, true);
    }

    protected function canEditRequestData($userId, LeavePermission $leavePermission): bool
    {
        if ($this->isAdmin($userId)) {
            return true;
        }

        if (!$this->isItUser($userId)) {
            return false;
        }

        return $this->canAccessLeavePermission($userId, $leavePermission);
    }

    protected function canEditCurrentStatus(LeavePermission $leavePermission): bool
    {
        return in_array((string) $leavePermission->status, ['pending', 'approved'], true);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'leave-permission');

        $userId = Auth::id();
        
        // Get visible department IDs
        $visibleDeptIds = $this->getVisibleDepartmentIds($userId);

        $query = LeavePermission::query()
            ->with([
                'employee',
                'employee.department',
                'employee.position',
                'user',
                'user.department',
                'reviewer',
            ])
            ->where(function ($q) use ($visibleDeptIds) {
                // Preferred: filter by employee.department_id
                $q->whereHas('employee', function ($qq) use ($visibleDeptIds) {
                    $qq->whereIn('department_id', $visibleDeptIds);
                })
                // Legacy fallback: filter by user.department_id
                ->orWhereHas('user', function ($qq) use ($visibleDeptIds) {
                    $qq->whereIn('department_id', $visibleDeptIds);
                });
            });

        // Filters
        $search = request('search');
        $status = request('status');
        $type = request('type');
        $startDate = request('start_date');
        $endDate = request('end_date');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%");
                })->orWhereHas('user', function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
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

        $leavePermissions = $query
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $leavePermissions->getCollection()->transform(function ($item) {
            $item->image_url = $item->attachment_image ? Storage::url($item->attachment_image) : null;
            return $item;
        });

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
            'canEditLeavePermission' => $this->isAdmin($userId) || $this->isItUser($userId),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $userId = Auth::id();
        $canSubmitForOthers = $this->isAdmin($userId) || $this->isManager($userId) || $this->isSupervisor($userId);

        $employees = [];
        if ($canSubmitForOthers) {

            $visibleDeptIds = $this->getVisibleDepartmentIds($userId);
            if (empty($visibleDeptIds)) {
                $employees = [];
            } else {

                // Dropdown uses Employee (not User). We only include employees that have a linked user,
                // because leave/permission is tracked by employee.
                $employees = Employee::query()
                    ->with([
                        'department:id,name',
                        'position:id,name',
                    ])
                    ->whereNotNull('department_id')
                    ->whereIn('department_id', $visibleDeptIds)
                    ->select('id', 'name', 'nik', 'department_id', 'position_id')
                    ->orderBy('name')
                    ->get()
                    ->map(function (Employee $emp) {
                        $name = trim((string) ($emp->name ?? ''));
                        $nik = trim((string) ($emp->nik ?? ''));
                        $dept = trim((string) optional($emp->department)->name);
                        $pos = trim((string) optional($emp->position)->name);

                        $label = $name !== '' ? $name : ('Employee #' . $emp->id);
                        $metaParts = array_values(array_filter([$dept, $pos]));
                        if (!empty($metaParts)) {
                            $label .= ' - ' . implode(' / ', $metaParts);
                        }
                        if ($nik !== '') {
                            $label .= " ({$nik})";
                        }

                        return [
                            'id' => $emp->id,
                            'label' => $label,
                            'department_id' => $emp->department_id,
                        ];
                    })
                    ->values()
                    ->all();
            }
        } else {
            // Regular user: only allow selecting self (employee).
            $actorEmployee = Employee::query()
                ->with([
                    'department:id,name',
                    'position:id,name',
                ])
                ->where('user_id', $userId)
                ->first();

            if ($actorEmployee) {
                $name = trim((string) ($actorEmployee->name ?? ''));
                $nik = trim((string) ($actorEmployee->nik ?? ''));
                $dept = trim((string) optional($actorEmployee->department)->name);
                $pos = trim((string) optional($actorEmployee->position)->name);

                $label = $name !== '' ? $name : ('Employee #' . $actorEmployee->id);
                $metaParts = array_values(array_filter([$dept, $pos]));
                if (!empty($metaParts)) {
                    $label .= ' - ' . implode(' / ', $metaParts);
                }
                if ($nik !== '') {
                    $label .= " ({$nik})";
                }

                $employees = [[
                    'id' => $actorEmployee->id,
                    'label' => $label,
                    'department_id' => $actorEmployee->department_id,
                ]];
            } else {
                $employees = [];
            }
        }

        $canSelectEmployee = !empty($employees);
        $defaultEmployeeId = Employee::where('user_id', $userId)->value('id') ?? '';

        return Inertia::render('GMIHR/LeavePermission/Create', [
            'employees' => $employees,
            'canSelectEmployee' => $canSelectEmployee,
            'canSubmitForOthers' => $canSubmitForOthers,
            'defaultEmployeeId' => $defaultEmployeeId,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeavePermission $leavePermission)
    {
        $userId = Auth::id();

        $leavePermission->load([
            'employee',
            'employee.department',
            'user',
            'user.department',
        ]);

        if (!$this->canEditRequestData($userId, $leavePermission)) {
            abort(403, 'Hanya user IT yang dapat mengedit permintaan ini.');
        }

        if (!$this->canEditCurrentStatus($leavePermission)) {
            abort(403, 'Hanya permintaan dengan status pending atau approved yang dapat diedit.');
        }

        $leavePermission->image_url = $leavePermission->attachment_image ? Storage::url($leavePermission->attachment_image) : null;

        return Inertia::render('GMIHR/LeavePermission/Edit', [
            'leavePermission' => $leavePermission,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(LeavePermission $leavePermission)
    {
        $userId = Auth::id();
        
        // Load the leave permission with related data
        $leavePermission->load([
            'employee',
            'employee.department',
            'employee.position',
            'user',
            'user.department',
            'reviewer',
        ]);
        $leavePermission->image_url = $leavePermission->attachment_image ? Storage::url($leavePermission->attachment_image) : null;

        return Inertia::render('GMIHR/LeavePermission/Show', [
            'leavePermission' => $leavePermission,
            'isAdmin' => $this->isAdmin($userId),
            'isManager' => $this->isManager($userId),
            'canEditLeavePermission' => $this->canEditRequestData($userId, $leavePermission),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'nullable|integer|exists:employees,id',
            'type' => 'required|in:cuti,izin,sakit,dinas_luar',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:5',
            'attachment_image' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        $actorId = Auth::id();
        $targetEmployeeId = null;

        $requestedEmployeeId = $data['employee_id'] ?? null;
        $canSubmitForOthers = $this->isAdmin($actorId) || $this->isManager($actorId) || $this->isSupervisor($actorId);
        $selfEmployeeId = (int) (Employee::where('user_id', $actorId)->value('id') ?? 0);

        if ($requestedEmployeeId !== null) {
            if (!$canSubmitForOthers) {
                if ($selfEmployeeId <= 0 || (int) $requestedEmployeeId !== $selfEmployeeId) {
                    return back()->withErrors([
                        'employee_id' => 'Anda tidak memiliki izin untuk memilih karyawan lain.',
                    ]);
                }
            }

            $targetEmployeeId = (int) $requestedEmployeeId;
        } else {
            // No selection provided: default to actor's employee record.
            $targetEmployeeId = $selfEmployeeId;
        }

        if ($targetEmployeeId <= 0) {
            return back()->withErrors([
                'employee_id' => 'Karyawan tidak ditemukan untuk akun ini. Silakan pilih karyawan.',
            ]);
        }

        $data['employee_id'] = $targetEmployeeId;
        $data['user_id'] = null; // employee-based, user is optional
        $data['days'] = LeavePermission::calculateDays($data['start_date'], $data['end_date']);
        $data['status'] = 'pending';
        $data['attachment_image'] = $request->hasFile('attachment_image')
            ? $request->file('attachment_image')->store('leave-permission-images', 'public')
            : null;

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

        return $this->redirectToRememberedIndex($request, 'leave-permission', 'leave-permission.index')
            ->with('success', 'Permintaan berhasil diajukan. Menunggu persetujuan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeavePermission $leavePermission)
    {
        $userId = Auth::id();
        $leavePermission->loadMissing([
            'employee:id,department_id',
            'user:id,department_id',
        ]);

        if ($request->has('status')) {
            if (!$this->canReviewLeavePermission($userId, $leavePermission)) {
                return response()->json([
                    'message' => 'Anda tidak memiliki izin untuk menyetujui/menolak permintaan ini.',
                ], 403);
            }

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

        if (!$this->canEditRequestData($userId, $leavePermission)) {
            abort(403, 'Hanya user IT yang dapat mengedit permintaan ini.');
        }

        if (!$this->canEditCurrentStatus($leavePermission)) {
            return back()->withErrors([
                'type' => 'Hanya permintaan dengan status pending atau approved yang dapat diedit.',
            ]);
        }

        $data = $request->validate([
            'type' => 'required|in:cuti,izin,sakit,dinas_luar',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:5',
            'attachment_image' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'remove_attachment' => 'nullable|boolean',
        ]);

        $oldData = $leavePermission->toArray();
        $updateData = [
            'type' => $data['type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days' => LeavePermission::calculateDays($data['start_date'], $data['end_date']),
            'reason' => $data['reason'],
        ];

        $removeAttachment = (bool) ($data['remove_attachment'] ?? false);
        if ($removeAttachment && $leavePermission->attachment_image) {
            Storage::disk('public')->delete($leavePermission->attachment_image);
            $updateData['attachment_image'] = null;
        }

        if ($request->hasFile('attachment_image')) {
            if ($leavePermission->attachment_image) {
                Storage::disk('public')->delete($leavePermission->attachment_image);
            }

            $updateData['attachment_image'] = $request->file('attachment_image')->store('leave-permission-images', 'public');
        }

        $leavePermission->update($updateData);

        $this->logActivity(
            'leave_permissions',
            $leavePermission->id,
            'updated',
            $oldData,
            $leavePermission->toArray(),
            'Updated leave permission request'
        );

        return redirect()->route('leave-permission.show', $leavePermission)
            ->with('success', 'Permintaan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, LeavePermission $leavePermission)
    {
        $userId = Auth::id();

        if (!$this->isAdmin($userId)) {
            return response()->json([
                'message' => 'Hanya admin yang dapat menghapus data ini.',
            ], 403);
        }

        if ($leavePermission->attachment_image) {
            Storage::disk('public')->delete($leavePermission->attachment_image);
        }

        $oldData = $leavePermission->toArray();
        $id = $leavePermission->id;
        $leavePermission->delete();

        $this->logActivity(
            'leave_permissions',
            $id,
            'deleted',
            $oldData,
            null,
            'Deleted leave permission request'
        );

        return $this->redirectToRememberedIndex($request, 'leave-permission', 'leave-permission.index')
            ->with('success', 'Data berhasil dihapus.');
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
