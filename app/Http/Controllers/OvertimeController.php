<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\Position;
use App\Support\AccessRuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
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
        if (! $userId) {
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
     * - Supervisor: departments of their direct reports
     * - Regular user: their own department only
     */
    protected function getVisibleDepartmentIds($userId)
    {
        $departmentIds = $this->accessRules()->visibleDepartmentIds($userId, self::ACCESS_MODULE, 'view_list');

        if ($this->isSupervisor($userId)) {
            $supervisorEmployeeId = (int) (Employee::query()
                ->where('user_id', $userId)
                ->value('id') ?? 0);

            if ($supervisorEmployeeId > 0) {
                $departmentIds = array_merge(
                    $departmentIds,
                    Employee::query()
                        ->where('reports_to', $supervisorEmployeeId)
                        ->whereNotNull('department_id')
                        ->distinct()
                        ->pluck('department_id')
                        ->map(fn ($id) => (int) $id)
                        ->all()
                );
            }
        }

        return array_values(array_unique(array_map('intval', $departmentIds)));
    }

    protected function getSubmissionDepartmentIds($userId): array
    {
        return $this->accessRules()->visibleDepartmentIds($userId, self::ACCESS_MODULE, 'submit_for_others');
    }

    protected function canViewAllOvertime($userId): bool
    {
        $accessRules = $this->accessRules();

        return $accessRules->isAdmin($userId)
            || $accessRules->isHr($userId)
            || $accessRules->isCfo($userId)
            || $accessRules->isIt($userId);
    }

    protected function canSubmitForOthers($userId): bool
    {
        return $this->accessRules()->allows($userId, self::ACCESS_MODULE, 'submit_for_others');
    }

    protected function canApproveDepartment($userId, int $departmentId): bool
    {
        return $this->accessRules()->canAccessDepartment($userId, self::ACCESS_MODULE, 'approve', $departmentId);
    }

    protected function canDeleteOvertime($userId): bool
    {
        return $this->accessRules()->allows($userId, self::ACCESS_MODULE, 'delete_request');
    }

    private function requiresSupervisorApproval(Employee $employee): bool
    {
        $departmentCode = strtoupper((string) Department::query()
            ->whereKey($employee->department_id)
            ->value('code'));

        return ! in_array($departmentCode, ['MNT', 'MTC', 'IT'], true);
    }

    private function supervisorForApproval(Employee $employee): ?Employee
    {
        return $employee->loadMissing(['supervisor.user', 'supervisor.position'])->supervisor;
    }

    private function canViewOvertime(Overtime $overtime, int $userId): bool
    {
        if ($this->canViewAllOvertime($userId)) {
            return true;
        }

        $targetDepartmentId = (int) ($overtime->employee?->department_id ?? 0);
        if ($targetDepartmentId <= 0) {
            $targetDepartmentId = (int) ($overtime->user?->department_id ?? 0);
        }

        if ($this->isManager($userId)) {
            return $this->accessRules()->canAccessDepartment(
                $userId,
                self::ACCESS_MODULE,
                'view_list',
                $targetDepartmentId
            );
        }

        if ($this->isSupervisor($userId)) {
            $supervisorEmployeeId = (int) (Employee::query()
                ->where('user_id', $userId)
                ->value('id') ?? 0);
            if ($supervisorEmployeeId <= 0) {
                return false;
            }

            if ($overtime->employee) {
                return (int) $overtime->employee->reports_to === $supervisorEmployeeId;
            }

            return Employee::query()
                ->where('user_id', $overtime->user_id)
                ->where('reports_to', $supervisorEmployeeId)
                ->exists();
        }

        $employeeId = (int) (Employee::where('user_id', $userId)->value('id') ?? 0);

        return $employeeId > 0 && (
            (int) $overtime->employee_id === $employeeId
            || ((int) $overtime->employee_id <= 0 && (int) $overtime->user_id === $userId)
        );
    }

    private function approvalConfigurationError(Employee $employee): ?string
    {
        if ($this->requiresSupervisorApproval($employee)) {
            $supervisor = $this->supervisorForApproval($employee);

            if (
                ! $supervisor?->user
                || $supervisor->user->status !== 'active'
                || (Schema::hasColumn('employees', 'employment_status') && $supervisor->employment_status !== 'active')
                || ! $this->isSupervisor($supervisor->user_id)
            ) {
                return 'Karyawan belum memiliki SPV aktif pada field Reporting To.';
            }
        }

        $managerIds = Employee::query()
            ->whereNotNull('user_id')
            ->whereHas('position', fn ($query) => $query->where('is_manager', true))
            ->whereHas('user', fn ($query) => $query->where('status', 'active'))
            ->when(Schema::hasColumn('employees', 'employment_status'), fn ($query) => $query->where('employment_status', 'active'))
            ->pluck('user_id');
        $hasManager = $managerIds->contains(
            fn ($managerId) => $this->accessRules()->canAccessDepartment(
                (int) $managerId,
                self::ACCESS_MODULE,
                'approve',
                (int) $employee->department_id
            )
        );

        return $hasManager ? null : 'Departemen karyawan belum memiliki Manager aktif.';
    }

    private function canApproveStage(Overtime $overtime, int $userId): bool
    {
        $overtime->loadMissing(['employee.supervisor', 'employee.user']);
        $employee = $overtime->employee;
        if (! $employee || (int) $employee->user_id === $userId) {
            return false;
        }

        $isSubmitter = (int) $overtime->user_id === $userId;

        if ($overtime->status === 'pending_spv') {
            $supervisor = $this->supervisorForApproval($employee);

            return $this->isSupervisor($userId)
                && (int) ($supervisor?->user_id ?? 0) === $userId
                && $supervisor?->user?->status === 'active'
                && (! Schema::hasColumn('employees', 'employment_status') || $supervisor->employment_status === 'active');
        }

        if ($overtime->status !== 'pending_manager' || ! $this->isManager($userId) || $isSubmitter) {
            return false;
        }

        $departmentId = (int) ($employee->department_id ?? 0);

        return $departmentId > 0
            && $this->accessRules()->canAccessDepartment(
                $userId,
                self::ACCESS_MODULE,
                'approve',
                $departmentId
            );
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'overtime');

        $userId = Auth::id();
        $isHr = $this->accessRules()->isHr($userId);
        $canSubmitAnyDate = $isHr;

        $canViewAll = $this->canViewAllOvertime($userId);
        $visibleDeptIds = $this->getVisibleDepartmentIds($userId);
        $submissionEmployees = [];

        if ($isHr) {
            $submissionEmployees = Employee::query()
                ->with(['department:id,name', 'position:id,name'])
                ->whereNotNull('department_id')
                ->when(
                    Schema::hasColumn('employees', 'employment_status'),
                    fn ($query) => $query->where('employment_status', 'active')
                )
                ->select('id', 'name', 'nik', 'department_id', 'position_id')
                ->orderBy('name')
                ->get()
                ->map(function (Employee $employee) {
                    $label = trim((string) $employee->name) ?: 'Employee #'.$employee->id;
                    $metaParts = array_values(array_filter([
                        trim((string) optional($employee->department)->name),
                        trim((string) optional($employee->position)->name),
                    ]));

                    if ($metaParts !== []) {
                        $label .= ' - '.implode(' / ', $metaParts);
                    }
                    if (trim((string) $employee->nik) !== '') {
                        $label .= ' ('.trim((string) $employee->nik).')';
                    }

                    return [
                        'id' => $employee->id,
                        'label' => $label,
                        'department_id' => $employee->department_id,
                    ];
                })
                ->values()
                ->all();
        }

        $query = Overtime::query();

        if (Schema::hasColumn('overtimes', 'employee_id')) {
            $query = $query
                ->with([
                    'employee:id,user_id,name,alias_name,nik,department_id,position_id,reports_to',
                    'employee.department:id,name',
                    'employee.position:id,name',
                    'user:id,name,email,department_id',
                    'user.department:id,name',
                    'reviewer',
                    'spvApprover',
                    'managerApprover',
                ]);
            if (! $canViewAll) {
                if ($this->isManager($userId)) {
                    if (empty($visibleDeptIds)) {
                        $query->whereIn('id', []);
                    } else {
                        $query->where(function ($q) use ($visibleDeptIds) {
                            $q->whereHas('employee', function ($employeeQuery) use ($visibleDeptIds) {
                                $employeeQuery->whereIn('department_id', $visibleDeptIds);
                            })->orWhere(function ($legacy) use ($visibleDeptIds) {
                                $legacy->whereNull('employee_id')
                                    ->whereHas('user', function ($userQuery) use ($visibleDeptIds) {
                                        $userQuery->whereIn('department_id', $visibleDeptIds);
                                    });
                            });
                        });
                    }
                } elseif ($this->isSupervisor($userId)) {
                    $supervisorEmployeeId = (int) (Employee::query()
                        ->where('user_id', $userId)
                        ->value('id') ?? 0);

                    if ($supervisorEmployeeId <= 0) {
                        $query->whereIn('id', []);
                    } else {
                        $query->where(function ($q) use ($supervisorEmployeeId) {
                            $q->whereHas('employee', function ($employeeQuery) use ($supervisorEmployeeId) {
                                $employeeQuery->where('reports_to', $supervisorEmployeeId);
                            })->orWhere(function ($legacy) use ($supervisorEmployeeId) {
                                $legacy->whereNull('employee_id')
                                    ->whereHas('user', function ($userQuery) use ($supervisorEmployeeId) {
                                        $userQuery->whereIn(
                                            'id',
                                            Employee::query()
                                                ->select('user_id')
                                                ->where('reports_to', $supervisorEmployeeId)
                                                ->whereNotNull('user_id')
                                        );
                                    });
                            });
                        });
                    }
                } else {
                    $employeeId = (int) (Employee::where('user_id', $userId)->value('id') ?? 0);
                    $query->where(function ($q) use ($employeeId, $userId) {
                        if ($employeeId > 0) {
                            $q->where('employee_id', $employeeId);
                        }
                        $q->orWhere(function ($legacy) use ($userId) {
                            $legacy->whereNull('employee_id')->where('user_id', $userId);
                        });
                    });
                }
            }
        } else {
            $query = $query->with(['user', 'user.department', 'reviewer']);
            if (! $canViewAll) {
                if ($this->isManager($userId)) {
                    $query->whereHas('user', function ($userQuery) use ($visibleDeptIds) {
                        $userQuery->whereIn('department_id', $visibleDeptIds);
                    });
                } elseif ($this->isSupervisor($userId)) {
                    $supervisorEmployeeId = (int) (Employee::query()
                        ->where('user_id', $userId)
                        ->value('id') ?? 0);
                    $query->whereHas('user', function ($userQuery) use ($supervisorEmployeeId) {
                        $userQuery->whereIn(
                            'id',
                            Employee::query()
                                ->select('user_id')
                                ->where('reports_to', $supervisorEmployeeId)
                                ->whereNotNull('user_id')
                        );
                    });
                } else {
                    $query->where('user_id', (int) $userId);
                }
            }
        }

        // Filters
        $search = request('search');
        $status = request('status');
        $departmentId = (int) request('department_id');
        $startDate = request('start_date');
        $endDate = request('end_date');

        $dateRules = $request->boolean('export')
            ? ['required', 'date']
            : ['nullable', 'date'];
        $endDateRules = $dateRules;

        if ($request->filled('start_date')) {
            $endDateRules[] = 'after_or_equal:start_date';
        }

        $request->validate([
            'start_date' => $dateRules,
            'end_date' => $endDateRules,
        ], [
            'start_date.required' => 'Tanggal Dari wajib diisi sebelum export.',
            'end_date.required' => 'Tanggal Sampai wajib diisi sebelum export.',
            'end_date.after_or_equal' => 'Tanggal Sampai harus sama dengan atau setelah Tanggal Dari.',
        ]);

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
        if (! empty($status)) {
            if ($status === 'pending') {
                $query->whereIn('status', ['pending', 'pending_spv', 'pending_manager']);
            } else {
                $query->where('status', $status);
            }
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
            ->orderByRaw("CASE WHEN status IN ('pending', 'pending_spv', 'pending_manager') THEN 0 ELSE 1 END")
            ->orderBy('overtime_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->boolean('export')) {
            $rows = (clone $orderedQuery)->get();

            return $this->exportOvertimesToExcel($rows);
        }

        $overtimes = (clone $orderedQuery)
            ->paginate(10)
            ->withQueryString();

        $overtimes->getCollection()->transform(function (Overtime $overtime) use ($userId) {
            $overtime->can_approve = $this->canApproveStage($overtime, (int) $userId);
            $overtime->approval_stage_label = match ($overtime->status) {
                'pending_spv' => 'Menunggu SPV',
                'pending_manager' => 'Menunggu Manager',
                'pending' => 'Menunggu SPV',
                default => ucfirst((string) $overtime->status),
            };

            return $overtime;
        });

        // Get departments for filter (only visible ones)
        $departments = Department::whereIn('id', $visibleDeptIds)
            ->select('id', 'name')
            ->get();

        return Inertia::render('GMIHR/Overtime/Index', [
            'overtimes' => $overtimes,
            'filters' => request()->only(['search', 'status', 'department_id', 'start_date', 'end_date', 'page']),
            'departments' => $departments,
            'employees' => $submissionEmployees,
            'canSelectEmployee' => $isHr && $submissionEmployees !== [],
            'canSubmitForOthers' => $isHr,
            'defaultEmployeeId' => Employee::where('user_id', $userId)->value('id') ?? '',
            'earliestOvertimeDate' => now('Asia/Jakarta')->subDay()->toDateString(),
            'canSubmitAnyDate' => $canSubmitAnyDate,
            'isAdmin' => $this->isAdmin($userId),
            'isManager' => $this->isManager($userId),
            'canViewAllOvertime' => $canViewAll,
            'canDeleteOvertime' => $this->canDeleteOvertime($userId),
        ]);
    }

    private function exportOvertimesToExcel(Collection $rows)
    {
        $spreadsheet = new Spreadsheet;
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
            $cell = Coordinate::stringFromColumnIndex($index + 1).'1';
            $sheet->setCellValue($cell, $header);
        }

        $rowIndex = 2;
        foreach ($rows as $row) {
            $createdAt = $row->created_at instanceof Carbon ? $row->created_at->format('Y-m-d H:i:s') : (string) ($row->created_at ?? '');
            $employeeName = (string) (optional($row->employee)->alias_name ?? optional($row->employee)->name ?? optional($row->user)->name ?? '-');
            $departmentName = (string) (optional(optional($row->employee)->department)->name ?? optional(optional($row->user)->department)->name ?? '-');
            $overtimeDate = $row->overtime_date instanceof Carbon ? $row->overtime_date->format('Y-m-d') : (string) ($row->overtime_date ?? '');
            $reviewedAt = $row->reviewed_at instanceof Carbon ? $row->reviewed_at->format('Y-m-d H:i:s') : (string) ($row->reviewed_at ?? '');

            $sheet->setCellValue('A'.$rowIndex, $createdAt);
            $sheet->setCellValue('B'.$rowIndex, $employeeName);
            $sheet->setCellValue('C'.$rowIndex, $departmentName);
            $sheet->setCellValue('D'.$rowIndex, $overtimeDate);
            $sheet->setCellValue('E'.$rowIndex, (string) ($row->start_time ?? '-'));
            $sheet->setCellValue('F'.$rowIndex, (string) ($row->end_time ?? '-'));
            $sheet->setCellValue('G'.$rowIndex, (string) ($row->hours ?? '-'));
            $sheet->setCellValue('H'.$rowIndex, (string) ($row->reason ?? '-'));
            $sheet->setCellValue('I'.$rowIndex, (string) ($row->status ?? '-'));
            $sheet->setCellValue('J'.$rowIndex, (string) (optional($row->reviewer)->name ?? '-'));
            $sheet->setCellValue('K'.$rowIndex, $reviewedAt !== '' ? $reviewedAt : '-');
            $sheet->setCellValue('L'.$rowIndex, (string) ($row->review_notes ?? '-'));
            $sheet->setCellValue('M'.$rowIndex, (string) ($row->attachment_url ?? '-'));
            $rowIndex++;
        }

        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'overtime_'.now()->format('Ymd_His').'.xlsx';
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
        $isHr = $this->accessRules()->isHr($userId);
        $canSubmitForOthers = $this->canSubmitForOthers($userId) || $isHr;

        $employees = [];

        if ($canSubmitForOthers) {
            $visibleDeptIds = $isHr
                ? Department::query()->pluck('id')->map(fn ($id) => (int) $id)->all()
                : $this->getSubmissionDepartmentIds($userId);

            if (! empty($visibleDeptIds)) {
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

                        $label = $name !== '' ? $name : ('Employee #'.$emp->id);
                        $metaParts = array_values(array_filter([$dept, $pos]));
                        if (! empty($metaParts)) {
                            $label .= ' - '.implode(' / ', $metaParts);
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

                $label = $name !== '' ? $name : ('Employee #'.$actorEmployee->id);
                $metaParts = array_values(array_filter([$dept, $pos]));
                if (! empty($metaParts)) {
                    $label .= ' - '.implode(' / ', $metaParts);
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

        $canSelectEmployee = ! empty($employees);
        $defaultEmployeeId = Employee::where('user_id', $userId)->value('id') ?? '';

        return Inertia::render('GMIHR/Overtime/Create', [
            'employees' => $employees,
            'canSelectEmployee' => $canSelectEmployee,
            'canSubmitForOthers' => $canSubmitForOthers,
            'defaultEmployeeId' => $defaultEmployeeId,
            'earliestOvertimeDate' => now('Asia/Jakarta')->subDay()->toDateString(),
            'canSubmitAnyDate' => $isHr,
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
                'spvApprover',
                'managerApprover',
            ]);
        } else {
            $overtime->load(['user', 'user.department', 'reviewer']);
        }

        $targetDeptId = (int) optional($overtime->employee)->department_id;
        if ($targetDeptId <= 0) {
            $targetDeptId = (int) optional($overtime->user)->department_id;
        }

        abort_unless($this->canViewOvertime($overtime, (int) $userId), 403);
        $canEditDetails = $this->isAdmin($userId) || $this->canApproveDepartment($userId, $targetDeptId);

        return Inertia::render('GMIHR/Overtime/Show', [
            'overtime' => $overtime,
            'isAdmin' => $this->isAdmin($userId),
            'isManager' => $this->isManager($userId),
            'canEditDetails' => $canEditDetails,
            'canApprove' => $this->canApproveStage($overtime, (int) $userId),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (! Schema::hasColumn('overtimes', 'employee_id')) {
            return back()->withErrors([
                'employee_id' => 'Fitur pilih karyawan (single/multi) butuh update database. Jalankan php artisan migrate terlebih dahulu.',
            ]);
        }

        $actorId = Auth::id();
        $isHr = $this->accessRules()->isHr($actorId);
        $data = $request->validate([
            'employee_id' => 'nullable|integer|exists:employees,id',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'integer|exists:employees,id',
            'overtime_date' => $isHr
                ? ['required', 'date']
                : [
                    'required',
                    'date',
                    'after_or_equal:'.now('Asia/Jakarta')->subDay()->toDateString(),
                ],
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
        ], [
            'overtime_date.after_or_equal' => 'Pengajuan lembur hanya dapat dilakukan paling lambat H+1 setelah tanggal lembur.',
        ]);

        $requestedEmployeeId = $data['employee_id'] ?? null;
        $requestedEmployeeIds = collect($data['employee_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $canSubmitForOthers = $this->canSubmitForOthers($actorId) || $isHr;

        if ($requestedEmployeeId === null && empty($requestedEmployeeIds)) {
            $requestedEmployeeId = (int) (Employee::where('user_id', $actorId)->value('id') ?? 0);
        }

        if (! empty($requestedEmployeeIds) && ! $canSubmitForOthers) {
            return back()->withErrors([
                'employee_ids' => 'Anda tidak memiliki izin untuk memilih banyak karyawan.',
            ]);
        }

        $targetEmployeeIds = ! empty($requestedEmployeeIds)
            ? $requestedEmployeeIds
            : [((int) $requestedEmployeeId)];

        if (count($targetEmployeeIds) === 1 && $targetEmployeeIds[0] > 0 && ! $canSubmitForOthers) {
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
            ->select('id', 'user_id', 'department_id', 'position_id', 'reports_to', 'employment_status')
            ->whereIn('id', $targetEmployeeIds)
            ->get()
            ->keyBy('id');

        foreach ($targetEmployeeIds as $empId) {
            if (! $employees->has($empId)) {
                return back()->withErrors([
                    'employee_id' => 'Ada karyawan yang tidak ditemukan.',
                ]);
            }
        }

        $visibleDeptIds = $isHr
            ? Department::query()->pluck('id')->map(fn ($id) => (int) $id)->all()
            : $this->getSubmissionDepartmentIds($actorId);
        foreach ($targetEmployeeIds as $empId) {
            $employee = $employees->get($empId);
            if (! $employee) {
                continue;
            }

            $deptId = (int) ($employee->department_id ?? 0);
            if ($deptId <= 0 || ! in_array($deptId, $visibleDeptIds, true)) {
                abort(403, 'Anda tidak memiliki izin untuk memilih karyawan departemen ini.');
            }

            if (Schema::hasColumn('employees', 'employment_status')) {
                if (trim((string) ($employee->employment_status ?? 'active')) !== 'active') {
                    return back()->withErrors([
                        'employee_id' => 'Ada karyawan yang sudah non active.',
                    ]);
                }
            }

            $configurationError = $this->approvalConfigurationError($employee);
            if ($configurationError !== null) {
                return back()->withErrors([
                    'employee_id' => $configurationError,
                ]);
            }
        }

        unset($data['employee_id'], $data['employee_ids']);
        $data['user_id'] = (int) $actorId;
        $data['hours'] = Overtime::calculateHours($data['start_time'], $data['end_time']);

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
            $employee = $employees->get($empId);
            $payload['status'] = $employee && $this->requiresSupervisorApproval($employee)
                ? 'pending_spv'
                : 'pending_manager';
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
                'Created overtime request for '.$data['overtime_date']
            );
        }

        return $this->redirectToRememberedIndex($request, 'overtime', 'overtime.index')
            ->with('success', 'Permintaan overtime berhasil diajukan ('.$createdCount.' karyawan). Menunggu persetujuan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Overtime $overtime)
    {
        $userId = Auth::id();
        if (! $userId) {
            abort(403);
        }

        if ((string) $request->input('action') === 'edit') {
            $overtime->loadMissing(['employee:id,user_id,department_id,position_id,reports_to', 'user:id,department_id']);
            $editTargetDeptId = (int) optional($overtime->employee)->department_id;
            if ($editTargetDeptId <= 0) {
                $editTargetDeptId = (int) optional($overtime->user)->department_id;
            }

            if (! $this->isAdmin($userId) && ! $this->canApproveDepartment($userId, $editTargetDeptId)) {
                abort(403, 'Anda tidak memiliki izin untuk mengedit detail overtime ini.');
            }

            $data = $request->validate([
                'action' => 'required|in:edit',
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
                'remove_attachment' => 'nullable|boolean',
            ]);

            $oldData = $overtime->toArray();
            $breakHours = (float) ($overtime->break_hours ?? 0);
            $grossHours = Overtime::calculateHours($data['start_time'], $data['end_time']);
            if ($breakHours >= $grossHours) {
                return back()->withErrors([
                    'end_time' => 'Jam lembur harus lebih lama dari durasi break yang sudah ditetapkan.',
                ]);
            }

            $payload = [
                'overtime_date' => $data['overtime_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'reason' => $data['reason'],
                'hours' => round($grossHours - $breakHours, 2),
            ];

            $removeAttachment = $request->boolean('remove_attachment');
            $currentAttachmentPath = trim((string) ($overtime->attachment_path ?? ''));

            if ($removeAttachment && $currentAttachmentPath !== '') {
                Storage::disk('public')->delete($currentAttachmentPath);
                $payload['attachment_path'] = null;
                $payload['attachment_original_name'] = null;
            }

            if ($request->hasFile('attachment')) {
                if ($currentAttachmentPath !== '') {
                    Storage::disk('public')->delete($currentAttachmentPath);
                }

                $file = $request->file('attachment');
                $payload['attachment_original_name'] = $file->getClientOriginalName();
                $payload['attachment_path'] = $file->storePublicly('overtime_attachments', ['disk' => 'public']);
            }

            $overtime->update($payload);

            $this->logActivity(
                'overtimes',
                $overtime->id,
                'updated',
                $oldData,
                $overtime->fresh()?->toArray(),
                'Updated overtime request details'
            );

            return redirect()->back()->with('success', 'Detail overtime berhasil diperbarui.');
        }

        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
            'review_notes' => 'nullable|string',
            'break_taken' => 'nullable|boolean',
            'break_hours' => $request->input('status') === 'approved' && $request->boolean('break_taken')
                ? 'required|numeric|min:0.01'
                : 'nullable|numeric|min:0',
        ]);

        $reviewerId = (int) Auth::id();
        $isApproval = $data['status'] === 'approved';
        $newStatus = '';

        DB::transaction(function () use ($request, $overtime, $data, $reviewerId, $isApproval, $userId, &$newStatus) {
            $lockedOvertime = Overtime::query()
                ->whereKey($overtime->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedOvertime->loadMissing(['employee:id,user_id,department_id,position_id,reports_to', 'user:id,department_id']);

            if (! $this->canApproveStage($lockedOvertime, (int) $userId)) {
                abort(403, 'Pengajuan ini belum berada pada tahap persetujuan Anda.');
            }

            $oldData = $lockedOvertime->toArray();
            $reviewedAt = now();
            $reviewData = [
                'review_notes' => $data['review_notes'] ?? null,
            ];

            if ($lockedOvertime->status === 'pending_spv') {
                $reviewData['spv_reviewed_by'] = $reviewerId;
                $reviewData['spv_reviewed_at'] = $reviewedAt;
                $reviewData['spv_review_notes'] = $reviewData['review_notes'] ?? null;
                $reviewData['status'] = $isApproval ? 'pending_manager' : 'rejected';

                if ($isApproval) {
                    $breakHours = $request->boolean('break_taken')
                        ? (float) ($data['break_hours'] ?? 0)
                        : 0;
                    $grossHours = Overtime::calculateHours(
                        $lockedOvertime->start_time->format('H:i'),
                        $lockedOvertime->end_time->format('H:i')
                    );

                    if ($request->boolean('break_taken') && $breakHours <= 0) {
                        throw ValidationException::withMessages([
                            'break_hours' => 'Durasi break harus lebih dari 0 jam.',
                        ]);
                    }

                    if ($breakHours >= $grossHours) {
                        throw ValidationException::withMessages([
                            'break_hours' => 'Durasi break harus lebih kecil dari total jam lembur.',
                        ]);
                    }

                    $reviewData['break_hours'] = $breakHours;
                    $reviewData['hours'] = round($grossHours - $breakHours, 2);
                }
            } elseif ($lockedOvertime->status === 'pending_manager') {
                $reviewData['manager_reviewed_by'] = $reviewerId;
                $reviewData['manager_reviewed_at'] = $reviewedAt;
                $reviewData['manager_review_notes'] = $reviewData['review_notes'] ?? null;
                $reviewData['status'] = $isApproval ? 'approved' : 'rejected';
            } else {
                abort(422, 'Status overtime ini sudah selesai diproses.');
            }

            if (in_array($reviewData['status'], ['approved', 'rejected'], true)) {
                $reviewData['reviewed_by'] = $reviewerId;
                $reviewData['reviewed_at'] = $reviewedAt;
            }

            $lockedOvertime->update($reviewData);
            $newStatus = $reviewData['status'];
            $action = $newStatus === 'pending_manager'
                ? 'approved_by_spv'
                : ($isApproval ? 'approved' : 'rejected');

            $this->logActivity(
                'overtimes',
                $lockedOvertime->id,
                $action,
                $oldData,
                $lockedOvertime->toArray(),
                ucfirst($action).' overtime request'
            );
        });

        $message = match ($newStatus) {
            'pending_manager' => 'Persetujuan SPV selesai. Permintaan diteruskan ke Manager.',
            'approved' => 'Permintaan overtime telah disetujui Manager.',
            default => 'Permintaan overtime telah ditolak.',
        };

        return redirect()->back()->with('success', $message);
    }

    /**
     * Remove the specified resource from storage (single delete).
     */
    public function destroy(Overtime $overtime)
    {
        $userId = Auth::id();
        if (! $userId || ! $this->canDeleteOvertime($userId)) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus data ini.');
        }

        $oldData = $overtime->toArray();
        $id = $overtime->id;

        $attachmentPath = trim((string) ($overtime->attachment_path ?? ''));
        if ($attachmentPath !== '') {
            Storage::disk('public')->delete($attachmentPath);
        }

        $overtime->delete();

        $this->logActivity(
            'overtimes',
            $id,
            'deleted',
            $oldData,
            null,
            'Deleted overtime request'
        );

        return $this->redirectToRememberedIndex(request(), 'overtime', 'overtime.index')
            ->with('success', 'Data overtime berhasil dihapus.');
    }

    /**
     * Bulk delete overtime requests.
     */
    public function destroyMany(Request $request)
    {
        $userId = Auth::id();

        if (! $this->canDeleteOvertime($userId)) {
            return response()->json([
                'message' => 'Anda tidak memiliki hak untuk menghapus data ini.',
            ], 403);
        }

        $payload = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:overtimes,id'],
        ]);

        $overtimes = Overtime::whereIn('id', $payload['ids'])->get();
        $deletedCount = 0;

        foreach ($overtimes as $overtime) {
            $attachmentPath = trim((string) ($overtime->attachment_path ?? ''));
            if ($attachmentPath !== '') {
                Storage::disk('public')->delete($attachmentPath);
            }

            $oldData = $overtime->toArray();
            $id = $overtime->id;
            $overtime->delete();

            $this->logActivity(
                'overtimes',
                $id,
                'deleted',
                $oldData,
                null,
                'Deleted overtime request'
            );

            $deletedCount++;
        }

        return response()->json([
            'message' => "{$deletedCount} data berhasil dihapus.",
            'deleted_count' => $deletedCount,
        ]);
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
