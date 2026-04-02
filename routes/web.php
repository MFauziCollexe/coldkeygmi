<?php

use App\Models\AttendanceHoliday;
use App\Models\Employee;
use App\Models\LeavePermission;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RosterController;
use App\Http\Controllers\DateCodeController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\VehicleTypeController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\AccessRuleService;

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

Route::get('/debug', function () {
    return response()->json([
        'user' => auth()->user(),
        'authenticated' => auth()->check(),
        'session_id' => session()->getId(),
    ]);
})->name('debug');

// Tickets (GMISL > Utility > Tickets) - protect route by module permission
Route::resource('tickets', App\Http\Controllers\TicketController::class)
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.tickets']);

// Ticket actions: update status, resolve, close, reopen
Route::post('tickets/{ticket}/update-status', [App\Http\Controllers\TicketController::class, 'updateStatus'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.tickets'])
    ->name('tickets.update-status');
Route::post('tickets/{ticket}/resolve', [App\Http\Controllers\TicketController::class, 'resolve'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.tickets'])
    ->name('tickets.resolve');
Route::post('tickets/{ticket}/close', [App\Http\Controllers\TicketController::class, 'close'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.tickets'])
    ->name('tickets.close');
Route::post('tickets/{ticket}/reopen', [App\Http\Controllers\TicketController::class, 'reopen'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.tickets'])
    ->name('tickets.reopen');

// Deadline change request and approval
Route::post('tickets/{ticket}/request-deadline', [App\Http\Controllers\TicketController::class, 'requestDeadlineChange'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.tickets'])
    ->name('tickets.request-deadline');
Route::post('tickets/{ticket}/approve-deadline', [App\Http\Controllers\TicketController::class, 'approveDeadlineChange'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.tickets'])
    ->name('tickets.approve-deadline');

// Distribute ticket to user (manager only)
Route::post('tickets/{ticket}/distribute', [App\Http\Controllers\TicketController::class, 'distribute'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.tickets'])
    ->name('tickets.distribute');

// Request Access (GMISL > Utility > Request Access) - protect route by module permission
Route::resource('request-access', App\Http\Controllers\RequestAccessController::class)
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.request_access']);

// Date Code (GMISL > Utility > Date Code)
Route::get('gmisl/utility/date-code', [DateCodeController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.date_code']);

// Berita Acara (GMISL > Utility > Berita Acara)
Route::get('gmisl/utility/berita-acara', [App\Http\Controllers\BeritaAcaraController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.berita_acara'])
    ->name('berita-acara.index');
Route::get('gmisl/utility/berita-acara/create', [App\Http\Controllers\BeritaAcaraController::class, 'create'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.berita_acara'])
    ->name('berita-acara.create');
Route::post('gmisl/utility/berita-acara', [App\Http\Controllers\BeritaAcaraController::class, 'store'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.berita_acara'])
    ->name('berita-acara.store');
Route::get('gmisl/utility/berita-acara/{beritaAcara}', [App\Http\Controllers\BeritaAcaraController::class, 'show'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.berita_acara'])
    ->name('berita-acara.show');
Route::get('gmisl/utility/berita-acara/{beritaAcara}/print', [App\Http\Controllers\BeritaAcaraController::class, 'print'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.berita_acara'])
    ->name('berita-acara.print');
Route::get('gmisl/utility/berita-acara/{beritaAcara}/pdf', [App\Http\Controllers\BeritaAcaraController::class, 'downloadPdf'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.berita_acara'])
    ->name('berita-acara.pdf');
Route::delete('gmisl/utility/berita-acara/{beritaAcara}', [App\Http\Controllers\BeritaAcaraController::class, 'destroy'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.berita_acara'])
    ->name('berita-acara.destroy');

// Check Inline (GMISL > Utility > Check Inline)
Route::get('check-inline', [App\Http\Controllers\CheckInlineController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.check_inline'])
    ->name('check-inline.index');
Route::get('check-inline/create', [App\Http\Controllers\CheckInlineController::class, 'create'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.check_inline'])
    ->name('check-inline.create');
Route::get('check-inline/{checkInline}', [App\Http\Controllers\CheckInlineController::class, 'show'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.check_inline'])
    ->name('check-inline.show');
Route::put('check-inline/{checkInline}', [App\Http\Controllers\CheckInlineController::class, 'update'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.check_inline'])
    ->name('check-inline.update');
Route::post('check-inline', [App\Http\Controllers\CheckInlineController::class, 'store'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.check_inline'])
    ->name('check-inline.store');

// GMIUM - Plugging
Route::get('gmium/plugging', [App\Http\Controllers\PluggingController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.plugging'])
    ->name('plugging.index');
Route::get('gmium/plugging/approval', [App\Http\Controllers\PluggingController::class, 'approvalIndex'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.plugging'])
    ->name('plugging.approval.index');
Route::get('gmium/plugging/create', [App\Http\Controllers\PluggingController::class, 'create'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.plugging'])
    ->name('plugging.create');
Route::post('gmium/plugging', [App\Http\Controllers\PluggingController::class, 'store'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.plugging'])
    ->name('plugging.store');
Route::get('gmium/plugging/{plugging}/edit', [App\Http\Controllers\PluggingController::class, 'edit'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.plugging'])
    ->name('plugging.edit');
Route::put('gmium/plugging/{plugging}', [App\Http\Controllers\PluggingController::class, 'update'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.plugging'])
    ->name('plugging.update');
Route::put('gmium/plugging/{plugging}/approve', [App\Http\Controllers\PluggingController::class, 'approve'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.plugging'])
    ->name('plugging.approve');
Route::delete('gmium/plugging/{plugging}', [App\Http\Controllers\PluggingController::class, 'destroy'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.plugging'])
    ->name('plugging.destroy');
Route::get('gmium/plugging-export', [App\Http\Controllers\PluggingController::class, 'export'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.plugging'])
    ->name('plugging.export');

// GMIUM - Resource Monitoring - Electricity - Standard Meter
Route::get('gmium/resource-monitoring/electricity/standard-meter', [App\Http\Controllers\ElectricityStandardMeterController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.resource_monitoring.electricity.standard_meter'])
    ->name('gmium.resource-monitoring.electricity.standard-meter.index');
Route::post('gmium/resource-monitoring/electricity/standard-meter', [App\Http\Controllers\ElectricityStandardMeterController::class, 'store'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.resource_monitoring.electricity.standard_meter'])
    ->name('gmium.resource-monitoring.electricity.standard-meter.store');
Route::put('gmium/resource-monitoring/electricity/standard-meter/{log}', [App\Http\Controllers\ElectricityStandardMeterController::class, 'update'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.resource_monitoring.electricity.standard_meter'])
    ->name('gmium.resource-monitoring.electricity.standard-meter.update');
Route::get('gmium/resource-monitoring/electricity/hv-meter', [App\Http\Controllers\ElectricityHvMeterController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.resource_monitoring.electricity.hv_meter'])
    ->name('gmium.resource-monitoring.electricity.hv-meter.index');
Route::post('gmium/resource-monitoring/electricity/hv-meter', [App\Http\Controllers\ElectricityHvMeterController::class, 'store'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.resource_monitoring.electricity.hv_meter'])
    ->name('gmium.resource-monitoring.electricity.hv-meter.store');
Route::put('gmium/resource-monitoring/electricity/hv-meter/{log}', [App\Http\Controllers\ElectricityHvMeterController::class, 'update'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.resource_monitoring.electricity.hv_meter'])
    ->name('gmium.resource-monitoring.electricity.hv-meter.update');
Route::get('gmium/resource-monitoring/electricity/hv-meter/export', [App\Http\Controllers\ElectricityHvMeterController::class, 'export'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.resource_monitoring.electricity.hv_meter'])
    ->name('gmium.resource-monitoring.electricity.hv-meter.export');

// GMIUM - Resource Monitoring - Water Meter
Route::get('gmium/resource-monitoring/water-meter', [App\Http\Controllers\WaterMeterController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.resource_monitoring.water_meter'])
    ->name('gmium.resource-monitoring.water-meter.index');
Route::post('gmium/resource-monitoring/water-meter', [App\Http\Controllers\WaterMeterController::class, 'store'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.resource_monitoring.water_meter'])
    ->name('gmium.resource-monitoring.water-meter.store');
Route::put('gmium/resource-monitoring/water-meter/{log}', [App\Http\Controllers\WaterMeterController::class, 'update'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.resource_monitoring.water_meter'])
    ->name('gmium.resource-monitoring.water-meter.update');
Route::get('gmium/resource-monitoring/water-meter/export', [App\Http\Controllers\WaterMeterController::class, 'export'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.resource_monitoring.water_meter'])
    ->name('gmium.resource-monitoring.water-meter.export');

// GMIUM - Utility Report
Route::get('gmium/utility-report', [App\Http\Controllers\UtilityReportController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmium.utility_report'])
    ->name('gmium.utility-report.index');

// GMIIC - Checklist
$resolveChecklistAbilities = static function (Request $request): array {
    $accessRules = app(AccessRuleService::class);
    $user = $request->user();

    return [
        'delete_entries' => $accessRules->allows($user, 'gmiic_checklist', 'delete_entries'),
        'kotak_p3k_hse_approve' => $accessRules->allows($user, 'gmiic_checklist', 'kotak_p3k_hse_approve'),
        'warehouse_final_approve' => $accessRules->allows($user, 'gmiic_checklist', 'warehouse_final_approve'),
    ];
};

Route::get('gmiic/checklist', function (Request $request) use ($resolveChecklistAbilities) {
    return Inertia::render('GMIIC/Checklist/Index', [
        'checklistAbilities' => $resolveChecklistAbilities($request),
    ]);
})->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmiic.checklist'])
    ->name('gmiic.checklist.index');
Route::get('gmiic/checklist/create', function (Request $request) use ($resolveChecklistAbilities) {
    $employees = Employee::query()
        ->with([
            'position:id,name,department_id',
            'position.department:id,name',
            'department:id,name',
            'user:id,position_id',
            'user.position:id,name,department_id',
            'user.position.department:id,name',
        ])
        ->when(Schema::hasColumn('employees', 'employment_status'), function ($query) {
            $query->where('employment_status', 'active');
        })
        ->orderBy('name')
        ->get();

    $employeeNikById = $employees
        ->filter(fn (Employee $employee) => !empty($employee->nik))
        ->mapWithKeys(fn (Employee $employee) => [$employee->id => $employee->nik]);

    $employeeNikByUserId = $employees
        ->filter(fn (Employee $employee) => !empty($employee->nik) && !empty($employee->user_id))
        ->mapWithKeys(fn (Employee $employee) => [$employee->user_id => $employee->nik]);

    $leaveDatesByNik = LeavePermission::query()
        ->where('status', 'approved')
        ->where('type', 'cuti')
        ->get(['employee_id', 'user_id', 'start_date', 'end_date'])
        ->reduce(function ($carry, LeavePermission $leave) use ($employeeNikById, $employeeNikByUserId) {
            $nik = $employeeNikById->get($leave->employee_id) ?: $employeeNikByUserId->get($leave->user_id);
            if (!$nik || !$leave->start_date || !$leave->end_date) {
                return $carry;
            }

            $cursor = \Illuminate\Support\Carbon::parse($leave->start_date)->startOfDay();
            $end = \Illuminate\Support\Carbon::parse($leave->end_date)->startOfDay();

            while ($cursor->lte($end)) {
                $carry[$nik] = $carry[$nik] ?? [];
                $carry[$nik][] = $cursor->format('Y-m-d');
                $cursor->addDay();
            }

            return $carry;
        }, []);

    return Inertia::render('GMIIC/Checklist/Create', [
        'selectedTemplate' => $request->string('template')->toString(),
        'entryId' => $request->string('entry_id')->toString(),
        'checklistAbilities' => $resolveChecklistAbilities($request),
        'holidayDates' => AttendanceHoliday::query()
            ->orderBy('holiday_date')
            ->pluck('holiday_date')
            ->map(fn ($date) => \Illuminate\Support\Carbon::parse($date)->format('Y-m-d'))
            ->values(),
        'leaveDatesByNik' => collect($leaveDatesByNik)
            ->map(fn ($dates) => array_values(array_unique($dates)))
            ->all(),
        'employees' => $employees
            ->map(function (Employee $employee) {
                $positionName = $employee->position?->name ?: $employee->user?->position?->name;

                return [
                    'id' => $employee->id,
                    'nik' => $employee->nik,
                    'name' => $employee->name,
                    'gender' => $employee->gender,
                    'bagian' => $positionName,
                    'position' => $positionName,
                ];
            })
            ->values(),
    ]);
})->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmiic.checklist'])
    ->name('gmiic.checklist.create');

// GMIVP - Visitor Form
Route::get('gmi-visitor-permit/visitor-form', [App\Http\Controllers\VisitorFormController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmi_visitor_permit.visitor_form'])
    ->name('gmi-visitor-permit.visitor-form.index');
Route::get('gmi-visitor-permit/visitor-form/create', [App\Http\Controllers\VisitorFormController::class, 'create'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmi_visitor_permit.visitor_form'])
    ->name('gmi-visitor-permit.visitor-form.create');
Route::post('gmi-visitor-permit/visitor-form', [App\Http\Controllers\VisitorFormController::class, 'store'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmi_visitor_permit.visitor_form'])
    ->name('gmi-visitor-permit.visitor-form.store');
Route::post('gmi-visitor-permit/visitor-form/{visitorForm}/status', [App\Http\Controllers\VisitorFormController::class, 'updateStatus'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmi_visitor_permit.visitor_form'])
    ->name('gmi-visitor-permit.visitor-form.update-status');
Route::post('gmi-visitor-permit/visitor-form/{visitorForm}/approve', [App\Http\Controllers\VisitorFormController::class, 'approve'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmi_visitor_permit.visitor_form'])
    ->name('gmi-visitor-permit.visitor-form.approve');

// GMIVP - Exit Permit (Surat Izin Keluar)
Route::get('gmi-visitor-permit/exit-permit', [App\Http\Controllers\ExitPermitController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmi_visitor_permit.exit_permit'])
    ->name('gmi-visitor-permit.exit-permit.index');
Route::get('gmi-visitor-permit/exit-permit/create', [App\Http\Controllers\ExitPermitController::class, 'create'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmi_visitor_permit.exit_permit'])
    ->name('gmi-visitor-permit.exit-permit.create');
Route::post('gmi-visitor-permit/exit-permit', [App\Http\Controllers\ExitPermitController::class, 'store'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmi_visitor_permit.exit_permit'])
    ->name('gmi-visitor-permit.exit-permit.store');
Route::post('gmi-visitor-permit/exit-permit/{exitPermit}/approve', [App\Http\Controllers\ExitPermitController::class, 'approve'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmi_visitor_permit.exit_permit'])
    ->name('gmi-visitor-permit.exit-permit.approve');

// Request Access custom actions: approve, reject, process
Route::post('request-access/{requestAccess}/approve', [App\Http\Controllers\RequestAccessController::class, 'approve'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.request_access'])
    ->name('request-access.approve');
Route::post('request-access/{requestAccess}/reject', [App\Http\Controllers\RequestAccessController::class, 'reject'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.request_access'])
    ->name('request-access.reject');
Route::post('request-access/{requestAccess}/process', [App\Http\Controllers\RequestAccessController::class, 'process'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.request_access'])
    ->name('request-access.process');

// Get users for existing_user type selection
Route::get('request-access/users', [App\Http\Controllers\RequestAccessController::class, 'getUsers'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':utility.request_access'])
    ->name('request-access.users');

// Master Data - Department (route: /master-data/department)
Route::resource('master-data/department', DepartmentController::class)
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmisl.master_data.department'])
    ->names('departments');

// Master Data - Customer (route: /master-data/customer)
Route::resource('master-data/customer', CustomerController::class)
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmisl.master_data.customer'])
    ->names('customers');

// Master Data - Vehicle Type (route: /master-data/vehicle-type)
Route::resource('master-data/vehicle-type', VehicleTypeController::class)
    ->except(['show'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmisl.master_data.vehicle_type'])
    ->names('vehicle-types');

// Master Data - Position (route: /master-data/position)
Route::resource('master-data/position', PositionController::class)
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmisl.master_data.position'])
    ->names('positions');

// Master Data - Employee (route: /master-data/employee)
Route::put('master-data/employee/{employee}/resign', [\App\Http\Controllers\EmployeeController::class, 'resign'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmisl.master_data.employee'])
    ->name('employees.resign');
Route::put('master-data/employee/{employee}/cancel-resign', [\App\Http\Controllers\EmployeeController::class, 'cancelResign'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmisl.master_data.employee'])
    ->name('employees.cancel-resign');
Route::resource('master-data/employee', \App\Http\Controllers\EmployeeController::class)
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmisl.master_data.employee'])
    ->names('employees');

// Control Panel - User Management (route: /control-panel/user)
Route::resource('control-panel/user', \App\Http\Controllers\MasterData\UserController::class)
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':control.users'])
    ->names('control.users');

// Control Panel - Module Control (admin or module access)
Route::get('control-panel/module-control', [App\Http\Controllers\ModuleControlController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':control.module'])
    ->name('control.module');
Route::post('control-panel/module-control/save', [App\Http\Controllers\ModuleControlController::class, 'save'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':control.module'])
    ->name('control.module.save');
Route::get('control-panel/module-control/user/{userId}', [App\Http\Controllers\ModuleControlController::class, 'forUser'])
    ->middleware(['auth']);

// Control Panel - Access Rules
Route::get('control-panel/access-rules', [App\Http\Controllers\AccessRuleController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':control.access_rules'])
    ->name('control.access-rules');
Route::post('control-panel/access-rules/save', [App\Http\Controllers\AccessRuleController::class, 'save'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':control.access_rules'])
    ->name('control.access-rules.save');
Route::post('control-panel/access-rules/rollback', [App\Http\Controllers\AccessRuleController::class, 'rollback'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':control.access_rules'])
    ->name('control.access-rules.rollback');
Route::delete('control-panel/access-rules/reset', [App\Http\Controllers\AccessRuleController::class, 'reset'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':control.access_rules'])
    ->name('control.access-rules.reset');

// Control Panel - Logs
Route::get('control-panel/logs', [App\Http\Controllers\ActivityLogController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':control.logs'])
    ->name('control.logs');
Route::delete('control-panel/logs/clear', [App\Http\Controllers\ActivityLogController::class, 'clear'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':control.logs'])
    ->name('control.logs.clear');

// GMIHR - Device Integration - Fingerprint
Route::get('fingerprint', [App\Http\Controllers\FingerprintController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.device.fingerprint'])
    ->name('fingerprint.index');
Route::post('fingerprint/preview', [App\Http\Controllers\FingerprintController::class, 'preview'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.device.fingerprint'])
    ->name('fingerprint.preview');
Route::post('fingerprint/confirm-save', [App\Http\Controllers\FingerprintController::class, 'confirmSave'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.device.fingerprint'])
    ->name('fingerprint.confirm-save');
Route::delete('fingerprint/clear', [App\Http\Controllers\FingerprintController::class, 'clear'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.device.fingerprint'])
    ->name('fingerprint.clear');
// Backward-compatible fingerprint routes
Route::get('gmihr/device/fingerprint', [App\Http\Controllers\FingerprintController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.device.fingerprint']);
Route::post('gmihr/device/fingerprint/preview', [App\Http\Controllers\FingerprintController::class, 'preview'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.device.fingerprint']);
Route::post('gmihr/device/fingerprint/confirm-save', [App\Http\Controllers\FingerprintController::class, 'confirmSave'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.device.fingerprint']);
Route::delete('gmihr/device/fingerprint/clear', [App\Http\Controllers\FingerprintController::class, 'clear'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.device.fingerprint']);

// GMIHR - Time & Attendance - Attendance Log
Route::get('attendance-log', [App\Http\Controllers\AttendanceLogController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.log'])
    ->name('attendance-log.index');
Route::post('attendance-log/corrections', [App\Http\Controllers\AttendanceLogController::class, 'storeCorrection'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.log'])
    ->name('attendance-log.corrections.store');
Route::post('attendance-log/corrections/{correction}/approve', [App\Http\Controllers\AttendanceLogController::class, 'approveCorrection'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.log'])
    ->name('attendance-log.corrections.approve');
Route::post('attendance-log/corrections/{correction}/reject', [App\Http\Controllers\AttendanceLogController::class, 'rejectCorrection'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.log'])
    ->name('attendance-log.corrections.reject');

Route::get('the-days', [App\Http\Controllers\TheDaysController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.the_days'])
    ->name('the-days.index');
Route::post('the-days', [App\Http\Controllers\TheDaysController::class, 'store'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.the_days'])
    ->name('the-days.store');
Route::delete('the-days/{theDay}', [App\Http\Controllers\TheDaysController::class, 'destroy'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.the_days'])
    ->name('the-days.destroy');

// GMIHR - Time & Attendance - Leave & Permission
Route::get('leave-permission', [App\Http\Controllers\LeavePermissionController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.leave_permission'])
    ->name('leave-permission.index');
Route::get('leave-permission/create', [App\Http\Controllers\LeavePermissionController::class, 'create'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.leave_permission'])
    ->name('leave-permission.create');
Route::get('leave-permission/{leavePermission}/edit', [App\Http\Controllers\LeavePermissionController::class, 'edit'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.leave_permission'])
    ->name('leave-permission.edit');
Route::post('leave-permission', [App\Http\Controllers\LeavePermissionController::class, 'store'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.leave_permission'])
    ->name('leave-permission.store');
Route::put('leave-permission/{leavePermission}', [App\Http\Controllers\LeavePermissionController::class, 'update'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.leave_permission'])
    ->name('leave-permission.update');
Route::delete('leave-permission/{leavePermission}', [App\Http\Controllers\LeavePermissionController::class, 'destroy'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.leave_permission'])
    ->name('leave-permission.destroy');
Route::get('leave-permission/{leavePermission}', [App\Http\Controllers\LeavePermissionController::class, 'show'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.leave_permission'])
    ->name('leave-permission.show');
// Backward-compatible leave permission routes
Route::get('gmihr/attendance/leave-permission', [App\Http\Controllers\LeavePermissionController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.leave_permission']);
Route::get('gmihr/attendance/leave-permission/create', [App\Http\Controllers\LeavePermissionController::class, 'create'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.leave_permission']);
Route::get('gmihr/attendance/leave-permission/{leavePermission}/edit', [App\Http\Controllers\LeavePermissionController::class, 'edit'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.leave_permission']);
Route::post('gmihr/attendance/leave-permission', [App\Http\Controllers\LeavePermissionController::class, 'store'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.leave_permission']);
Route::put('gmihr/attendance/leave-permission/{leavePermission}', [App\Http\Controllers\LeavePermissionController::class, 'update'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.leave_permission']);
Route::delete('gmihr/attendance/leave-permission/{leavePermission}', [App\Http\Controllers\LeavePermissionController::class, 'destroy'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.leave_permission']);
Route::get('gmihr/attendance/leave-permission/{leavePermission}', [App\Http\Controllers\LeavePermissionController::class, 'show'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.leave_permission']);

// GMIHR - Time & Attendance - Overtime
Route::get('overtime', [App\Http\Controllers\OvertimeController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.overtime'])
    ->name('overtime.index');
Route::get('overtime/create', [App\Http\Controllers\OvertimeController::class, 'create'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.overtime'])
    ->name('overtime.create');
Route::post('overtime', [App\Http\Controllers\OvertimeController::class, 'store'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.overtime'])
    ->name('overtime.store');
Route::put('overtime/{overtime}', [App\Http\Controllers\OvertimeController::class, 'update'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.overtime'])
    ->name('overtime.update');

// GMIHR - Time & Attendance - Overtime Show
Route::get('overtime/{overtime}', [App\Http\Controllers\OvertimeController::class, 'show'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.overtime'])
    ->name('overtime.show');
// Backward-compatible overtime routes
Route::get('gmihr/attendance/overtime', [App\Http\Controllers\OvertimeController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.overtime']);
Route::get('gmihr/attendance/overtime/create', [App\Http\Controllers\OvertimeController::class, 'create'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.overtime']);
Route::post('gmihr/attendance/overtime', [App\Http\Controllers\OvertimeController::class, 'store'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.overtime']);
Route::put('gmihr/attendance/overtime/{overtime}', [App\Http\Controllers\OvertimeController::class, 'update'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.overtime']);
Route::get('gmihr/attendance/overtime/{overtime}', [App\Http\Controllers\OvertimeController::class, 'show'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.overtime']);

// GMIHR - Time & Attendance - Approval
Route::get('attendance-approval', function () {
    return Inertia::render('GMIHR/AttendanceApproval/Index');
})->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.attendance.approval']);

// GMIHR - Payroll - Salary
Route::get('salary', function () {
    return Inertia::render('GMIHR/Salary/Index');
})->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.payroll.salary']);

// GMIHR - Payroll - Payslip
Route::get('payslip', function () {
    return Inertia::render('GMIHR/Payslip/Index');
})->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.payroll.payslip']);

// GMIHR - Time & Attendance - Roster
Route::get('roster', [RosterController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.payroll.roster.upload'])
    ->name('roster.index');
Route::get('roster/upload', [RosterController::class, 'uploadPage'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.payroll.roster.upload'])
    ->name('roster.upload.index');
Route::get('roster/list', [RosterController::class, 'listPage'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.payroll.roster.list'])
    ->name('roster.list.index');
Route::post('roster/preview', [RosterController::class, 'preview'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.payroll.roster.upload'])
    ->name('roster.preview');
Route::post('roster/upload', [RosterController::class, 'upload'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.payroll.roster.upload'])
    ->name('roster.upload');
Route::post('roster/{batch}/approve', [RosterController::class, 'approve'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.payroll.roster.list'])
    ->name('roster.approve');
Route::post('roster/{batch}/reject', [RosterController::class, 'reject'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.payroll.roster.list'])
    ->name('roster.reject');
Route::get('roster/{batch}/view', [RosterController::class, 'view'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.payroll.roster.list'])
    ->name('roster.view');
Route::get('roster/{batch}/download', [RosterController::class, 'download'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.payroll.roster.list'])
    ->name('roster.download');
Route::get('roster/template', [RosterController::class, 'template'])
    ->middleware(['auth', \App\Http\Middleware\EnsureModulePermission::class . ':gmihr.payroll.roster.upload'])
    ->name('roster.template');

// Attachment actions: delete and replace
Route::delete('tickets/{ticket}/attachments/{attachment}', [App\Http\Controllers\TicketController::class, 'destroyAttachment'])->middleware('auth')->name('tickets.attachments.destroy');
Route::post('tickets/{ticket}/attachments/{attachment}/replace', [App\Http\Controllers\TicketController::class, 'replaceAttachment'])->middleware('auth')->name('tickets.attachments.replace');

Route::get('/', function () {
    return Inertia::render('Login');
})->name('login');

Route::get('/signup', [UserController::class, 'showSignUp'])->name('signup');
Route::post('/signup', [UserController::class, 'store'])->name('signup.store');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// Forgot Password
Route::get('/forgot-password', [App\Http\Controllers\ForgotPasswordController::class, 'showForgotPassword'])->name('forgot-password');
Route::post('/forgot-password/verify', [App\Http\Controllers\ForgotPasswordController::class, 'verify'])->name('forgot-password.verify');
Route::get('/reset-password', [App\Http\Controllers\ForgotPasswordController::class, 'showResetPassword'])->name('reset-password');
Route::post('/reset-password', [App\Http\Controllers\ForgotPasswordController::class, 'reset'])->name('reset-password.post');

Route::get('/terms-and-conditions', function () {
    return Inertia::render('TermsAndConditions');
})->name('terms');

Route::get('/privacy-policy', function () {
    return Inertia::render('PrivacyPolicy');
})->name('privacy');

Route::get('/welcome', function () {
    return view('welcome');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->name('logout');
