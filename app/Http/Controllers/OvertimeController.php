<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\User;
use App\Models\Department;
use App\Models\ActivityLog;
use App\Support\AccessRuleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OvertimeController extends Controller
{
    use RemembersIndexUrl;

    private const ACCESS_MODULE = 'overtime';

    protected function accessRules(): AccessRuleService
    {
        return app(AccessRuleService::class);
    }

    protected function getActorEmployee($userId): ?Employee
    {
        if (!$userId) {
            return null;
        }

        return Employee::query()
            ->select('id', 'user_id', 'department_id', 'position_id', 'name', 'nik', 'employment_status')
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Check if user is admin (specific email or is_admin flag)
     */
    protected function isAdmin($userId)
    {
        return $this->accessRules()->isAdmin($userId);
    }

    /**
     * Check if user is a manager
     */
    protected function isManager($userId)
    {
        return $this->accessRules()->isManager($userId);
    }

    /**
     * Check if user is a supervisor.
     *
     * This project currently doesn't have an explicit `is_supervisor` flag on positions,
     * so we infer it from position `code` / `name` containing "SPV" / "Supervisor".
     */
    protected function isSupervisor($userId)
    {
        return $this->accessRules()->isSupervisor($userId);
    }

    /**
     * Get department IDs that user can view
     * - Admin: all departments
     * - Manager: their managed department
     * - Regular user: their own department only
     */
    protected function getVisibleDepartmentIds($userId)
    {
        return $this->accessRules()->visibleDepartmentIds($userId, self::ACCESS_MODULE, 'view_list');
    }

    protected function canSubmitForOthers($userId): bool
    {
        return $this->accessRules()->allows($userId, self::ACCESS_MODULE, 'submit_for_others');
    }

    protected function canApproveDepartment($userId, int $departmentId): bool
    {
        return $this->accessRules()->canAccessDepartment($userId, self::ACCESS_MODULE, 'approve', $departmentId);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'overtime');

        $userId = Auth::id();
        
        // Get visible department IDs
        $visibleDeptIds = $this->getVisibleDepartmentIds($userId);

        $query = Overtime::query();

        if (Schema::hasColumn('overtimes', 'employee_id')) {
            $query = $query
                ->with([
                    'employee:id,name,nik,department_id,position_id',
                    'employee.department:id,name',
                    'employee.position:id,name',
                    'user:id,name,email,department_id',
                    'user.department:id,name',
                    'reviewer',
                ])
                ->where(function ($q) use ($visibleDeptIds, $userId) {
                    $q->whereHas('employee', function ($empQuery) use ($visibleDeptIds) {
                        $empQuery->whereNotNull('department_id')
                            ->whereIn('department_id', $visibleDeptIds);
                    });

                    // Fallback: if employee_id is null or employee record missing, still show own submissions.
                    if ($userId) {
                        $q->orWhere('user_id', (int) $userId);
                    }
                });
        } else {
            // Backward compatibility if migration hasn't been run yet.
            $visibleUserIds = Employee::query()
                ->whereNotNull('user_id')
                ->whereIn('department_id', $visibleDeptIds)
                ->pluck('user_id')
                ->map(fn($id) => (int) $id)
                ->filter(fn($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if ($userId && !in_array((int) $userId, $visibleUserIds, true)) {
                $visibleUserIds[] = (int) $userId;
            }

            $query = $query
                ->with(['user', 'user.department', 'reviewer'])
                ->whereIn('user_id', $visibleUserIds);
        }

        // Filters
        $search = request('search');
        $status = request('status');
        $departmentId = (int) request('department_id');
        $startDate = request('start_date');
        $endDate = request('end_date');

        if ($search) {
            $query->where(function ($q) use ($search) {
                if (Schema::hasColumn('overtimes', 'employee_id')) {
                    $q->whereHas('employee', function ($empQuery) use ($search) {
                        $empQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('nik', 'like', "%{$search}%");
                    });
                }

                $q->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        // Filter status - only apply if status is not empty
        if (!empty($status)) {
            $query->where('status', $status);
        }

        if ($departmentId > 0) {
            $query->where(function ($q) use ($departmentId) {
                if (Schema::hasColumn('overtimes', 'employee_id')) {
                    $q->whereHas('employee', function ($empQuery) use ($departmentId) {
                        $empQuery->where('department_id', $departmentId);
                    });
                }

                $q->orWhereHas('user', function ($userQuery) use ($departmentId) {
                    $userQuery->where('department_id', $departmentId);
                });
            });
        }

        if ($startDate) {
            $query->where('overtime_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('overtime_date', '<=', $endDate);
        }

        $orderedQuery = $query
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('overtime_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->boolean('export')) {
            $rows = (clone $orderedQuery)->get();
            return $this->exportOvertimesToExcel($rows);
        }

        $overtimes = (clone $orderedQuery)
            ->paginate(10)
            ->withQueryString();

        // Get departments for filter (only visible ones)
        $departments = Department::whereIn('id', $visibleDeptIds)
            ->select('id', 'name')
            ->get();

        return Inertia::render('GMIHR/Overtime/Index', [
            'overtimes' => $overtimes,
            'filters' => request()->only(['search', 'status', 'department_id', 'start_date', 'end_date', 'page']),
            'departments' => $departments,
            'isAdmin' => $this->isAdmin($userId),
            'isManager' => $this->isManager($userId),
        ]);
    }

    private function exportOvertimesToExcel(Collection $rows)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Overtime');

        $headers = [
            'Tanggal Pengajuan',
            'Karyawan',
            'Department',
            'Tanggal Lembur',
            'Jam Mulai',
            'Jam Selesai',
            'Jumlah Jam',
            'Alasan',
            'Status',
            'Reviewer',
            'Reviewed At',
            'Review Notes',
            'Attachment',
        ];

        foreach ($headers as $index => $header) {
            $cell = Coordinate::stringFromColumnIndex($index + 1) . '1';
            $sheet->setCellValue($cell, $header);
        }

        $rowIndex = 2;
        foreach ($rows as $row) {
            $createdAt = $row->created_at instanceof Carbon ? $row->created_at->format('Y-m-d H:i:s') : (string) ($row->created_at ?? '');
            $employeeName = (string) (optional($row->employee)->name ?? optional($row->user)->name ?? '-');
            $departmentName = (string) (optional(optional($row->employee)->department)->name ?? optional(optional($row->user)->department)->name ?? '-');
            $overtimeDate = $row->overtime_date instanceof Carbon ? $row->overtime_date->format('Y-m-d') : (string) ($row->overtime_date ?? '');
            $reviewedAt = $row->reviewed_at instanceof Carbon ? $row->reviewed_at->format('Y-m-d H:i:s') : (string) ($row->reviewed_at ?? '');

            $sheet->setCellValue('A' . $rowIndex, $createdAt);
            $sheet->setCellValue('B' . $rowIndex, $employeeName);
            $sheet->setCellValue('C' . $rowIndex, $departmentName);
            $sheet->setCellValue('D' . $rowIndex, $overtimeDate);
            $sheet->setCellValue('E' . $rowIndex, (string) ($row->start_time ?? '-'));
            $sheet->setCellValue('F' . $rowIndex, (string) ($row->end_time ?? '-'));
            $sheet->setCellValue('G' . $rowIndex, (string) ($row->hours ?? '-'));
            $sheet->setCellValue('H' . $rowIndex, (string) ($row->reason ?? '-'));
            $sheet->setCellValue('I' . $rowIndex, (string) ($row->status ?? '-'));
            $sheet->setCellValue('J' . $rowIndex, (string) (optional($row->reviewer)->name ?? '-'));
            $sheet->setCellValue('K' . $rowIndex, $reviewedAt !== '' ? $reviewedAt : '-');
            $sheet->setCellValue('L' . $rowIndex, (string) ($row->review_notes ?? '-'));
            $sheet->setCellValue('M' . $rowIndex, (string) ($row->attachment_url ?? '-'));
            $rowIndex++;
        }

        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'overtime_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $userId = Auth::id();
        $canSubmitForOthers = $this->canSubmitForOthers($userId);

        $employees = [];

        if ($canSubmitForOthers) {
            $visibleDeptIds = $this->getVisibleDepartmentIds($userId);

            if (!empty($visibleDeptIds)) {
                $employeesQuery = Employee::query()
                    ->with([
                        'department:id,name',
                        'position:id,name',
                    ])
                    ->whereNotNull('department_id')
                    ->whereIn('department_id', $visibleDeptIds)
                    ->select('id', 'name', 'nik', 'department_id', 'position_id', 'employment_status')
                    ->orderBy('name');

                if (Schema::hasColumn('employees', 'employment_status')) {
                    $employeesQuery->where('employment_status', 'active');
                }

                $employees = $employeesQuery
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
            }
        }

        $canSelectEmployee = !empty($employees);
        $defaultEmployeeId = Employee::where('user_id', $userId)->value('id') ?? '';

        return Inertia::render('GMIHR/Overtime/Create', [
            'employees' => $employees,
            'canSelectEmployee' => $canSelectEmployee,
            'canSubmitForOthers' => $canSubmitForOthers,
            'defaultEmployeeId' => $defaultEmployeeId,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Overtime $overtime)
    {
        $userId = Auth::id();
        
        // Load the overtime with related data
        if (Schema::hasColumn('overtimes', 'employee_id')) {
            $overtime->load([
                'employee',
                'employee.department',
                'employee.position',
                'user',
                'user.department',
                'reviewer',
            ]);
        } else {
            $overtime->load(['user', 'user.department', 'reviewer']);
        }

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
        if (!Schema::hasColumn('overtimes', 'employee_id')) {
            return back()->withErrors([
                'employee_id' => 'Fitur pilih karyawan (single/multi) butuh update database. Jalankan php artisan migrate terlebih dahulu.',
            ]);
        }

        $data = $request->validate([
            'employee_id' => 'nullable|integer|exists:employees,id',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'integer|exists:employees,id',
            'overtime_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => [
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail) use ($request) {
                    $startTime = (string) $request->input('start_time', '');
                    if ($startTime === '' || $value === '') {
                        return;
                    }

                    if ($startTime === $value) {
                        $fail('Jam selesai harus berbeda dari jam mulai.');
                    }
                },
            ],
            'reason' => 'required|string|min:5',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        $actorId = Auth::id();
        $requestedEmployeeId = $data['employee_id'] ?? null;
        $requestedEmployeeIds = collect($data['employee_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $canSubmitForOthers = $this->canSubmitForOthers($actorId);

        if ($requestedEmployeeId === null && empty($requestedEmployeeIds)) {
            $requestedEmployeeId = (int) (Employee::where('user_id', $actorId)->value('id') ?? 0);
        }

        if (!empty($requestedEmployeeIds) && !$canSubmitForOthers) {
            return back()->withErrors([
                'employee_ids' => 'Anda tidak memiliki izin untuk memilih banyak karyawan.',
            ]);
        }

        $targetEmployeeIds = !empty($requestedEmployeeIds)
            ? $requestedEmployeeIds
            : [((int) $requestedEmployeeId)];

        if (count($targetEmployeeIds) === 1 && $targetEmployeeIds[0] > 0 && !$canSubmitForOthers) {
            // Regular user may only submit for self (employee derived from account).
            $selfEmployeeId = (int) (Employee::where('user_id', $actorId)->value('id') ?? 0);
            if ($selfEmployeeId <= 0 || $targetEmployeeIds[0] !== $selfEmployeeId) {
                return back()->withErrors([
                    'employee_id' => 'Anda tidak memiliki izin untuk memilih karyawan lain.',
                ]);
            }
        }

        if (empty($targetEmployeeIds) || min($targetEmployeeIds) <= 0) {
            return back()->withErrors([
                'employee_id' => 'Karyawan tidak ditemukan untuk akun ini. Silakan pilih karyawan.',
            ]);
        }

        $employees = Employee::query()
            ->select('id', 'department_id', 'employment_status')
            ->whereIn('id', $targetEmployeeIds)
            ->get()
            ->keyBy('id');

        foreach ($targetEmployeeIds as $empId) {
            if (!$employees->has($empId)) {
                return back()->withErrors([
                    'employee_id' => 'Ada karyawan yang tidak ditemukan.',
                ]);
            }
        }

        $visibleDeptIds = $this->isAdmin($actorId) ? [] : $this->getVisibleDepartmentIds($actorId);
        foreach ($targetEmployeeIds as $empId) {
            $employee = $employees->get($empId);
            if (!$employee) {
                continue;
            }

            if (!$this->isAdmin($actorId)) {
                $deptId = (int) ($employee->department_id ?? 0);
                if ($deptId <= 0 || !in_array($deptId, $visibleDeptIds, true)) {
                    abort(403, 'Anda tidak memiliki izin untuk memilih karyawan departemen ini.');
                }
            }

            if (Schema::hasColumn('employees', 'employment_status')) {
                if (trim((string) ($employee->employment_status ?? 'active')) !== 'active') {
                    return back()->withErrors([
                        'employee_id' => 'Ada karyawan yang sudah non active.',
                    ]);
                }
            }
        }

        unset($data['employee_id'], $data['employee_ids']);
        $data['user_id'] = (int) $actorId;
        $data['hours'] = Overtime::calculateHours($data['start_time'], $data['end_time']);
        $data['status'] = 'pending';

        $attachmentOriginalName = null;
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentOriginalName = $file->getClientOriginalName();
            $attachmentPath = $file->storePublicly('overtime_attachments', ['disk' => 'public']);
        }

        $createdCount = 0;
        foreach ($targetEmployeeIds as $empId) {
            $payload = $data;
            $payload['employee_id'] = (int) $empId;
            $payload['attachment_original_name'] = $attachmentOriginalName;
            $payload['attachment_path'] = $attachmentPath;

            $overtime = Overtime::create($payload);
            $createdCount += 1;

            // Activity Log for Create
            $this->logActivity(
                'overtimes',
                $overtime->id,
                'created',
                null,
                $payload,
                'Created overtime request for ' . $data['overtime_date']
            );
        }

        return $this->redirectToRememberedIndex($request, 'overtime', 'overtime.index')
            ->with('success', 'Permintaan overtime berhasil diajukan (' . $createdCount . ' karyawan). Menunggu persetujuan.');
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

        $overtime->loadMissing(['employee:id,department_id', 'user:id,department_id']);
        $targetDeptId = (int) optional($overtime->employee)->department_id;
        if ($targetDeptId <= 0) {
            $targetDeptId = (int) optional($overtime->user)->department_id;
        }

        // Only admin or manager of the requester's department can approve/reject.
        if (!$this->canApproveDepartment($userId, $targetDeptId)) {
            abort(403, 'Anda tidak memiliki izin untuk menyetujui/menolak overtime ini.');
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
