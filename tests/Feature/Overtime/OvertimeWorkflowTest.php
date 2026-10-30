<?php

namespace Tests\Feature\Overtime;

use App\Models\Overtime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OvertimeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_overtime_cannot_be_submitted_more_than_one_day_after_its_date(): void
    {
        $requester = $this->createUser([], 'gmihr.attendance.overtime');

        $this->actingAs($requester)
            ->from(route('overtime.create'))
            ->post(route('overtime.store'), [
                'overtime_date' => now('Asia/Jakarta')->subDays(2)->toDateString(),
                'start_time' => '18:00',
                'end_time' => '20:00',
                'reason' => 'Overtime submitted after the allowed window.',
            ])
            ->assertRedirect(route('overtime.create'))
            ->assertSessionHasErrors([
                'overtime_date' => 'Pengajuan lembur hanya dapat dilakukan paling lambat H+1 setelah tanggal lembur.',
            ]);

        $this->assertSame(0, Overtime::query()->count());
    }

    public function test_overtime_requires_supervisor_then_manager_approval(): void
    {
        $department = $this->createDepartment([
            'name' => 'Operations',
            'code' => 'OPS',
        ]);
        $requester = $this->createUser([
            'department' => $department,
        ], 'gmihr.attendance.overtime');
        $supervisorPosition = $this->createPosition($department, [
            'name' => 'Operations Supervisor',
            'code' => 'OPS-SPV',
        ]);
        $supervisor = $this->createUser([
            'department' => $department,
            'position' => $supervisorPosition,
        ], 'gmihr.attendance.overtime');
        $supervisorEmployee = $this->createEmployee($supervisor, [
            'department_id' => $department->id,
            'position_id' => $supervisorPosition->id,
        ]);
        $employee = $this->createEmployee($requester, [
            'department_id' => $department->id,
            'name' => 'Requester Employee',
            'reports_to' => $supervisorEmployee->id,
        ]);

        $managerPosition = $this->createPosition($department, [
            'name' => 'Operations Manager',
            'code' => 'OPS-MGR',
            'is_manager' => true,
        ]);
        $manager = $this->createUser([
            'department' => $department,
            'position' => $managerPosition,
        ], 'gmihr.attendance.overtime');
        $this->createEmployee($manager, [
            'department_id' => $department->id,
            'position_id' => $managerPosition->id,
        ]);

        $storeResponse = $this
            ->actingAs($requester)
            ->post(route('overtime.store'), [
                'employee_id' => $employee->id,
                'overtime_date' => now('Asia/Jakarta')->subDay()->toDateString(),
                'start_time' => '18:00',
                'end_time' => '20:30',
                'reason' => 'Support deployment after office hours.',
            ]);

        $storeResponse->assertRedirect(route('overtime.index'));
        $storeResponse->assertSessionHas('success');

        $overtime = Overtime::query()->firstOrFail();
        $this->assertSame('pending_spv', $overtime->status);
        $this->assertSame(2.5, (float) $overtime->hours);
        $this->assertSame($employee->id, $overtime->employee_id);

        $otherDepartment = $this->createDepartment([
            'name' => 'Finance',
            'code' => 'FIN',
        ]);
        $otherSupervisorPosition = $this->createPosition($otherDepartment, [
            'name' => 'Finance Supervisor',
            'code' => 'FIN-SPV',
        ]);
        $otherSupervisor = $this->createUser([
            'department' => $otherDepartment,
            'position' => $otherSupervisorPosition,
        ], 'gmihr.attendance.overtime');
        $this->createEmployee($otherSupervisor, [
            'department_id' => $otherDepartment->id,
            'position_id' => $otherSupervisorPosition->id,
        ]);

        $this->actingAs($otherSupervisor)
            ->from(route('overtime.index'))
            ->put(route('overtime.update', $overtime), [
                'status' => 'approved',
                'review_notes' => 'SPV from another department cannot approve.',
            ])
            ->assertForbidden();

        $peerSupervisorPosition = $this->createPosition($department, [
            'name' => 'Other Operations Supervisor',
            'code' => 'OPS-SPV-2',
        ]);
        $peerSupervisor = $this->createUser([
            'department' => $department,
            'position' => $peerSupervisorPosition,
        ], 'gmihr.attendance.overtime');
        $this->createEmployee($peerSupervisor, [
            'department_id' => $department->id,
            'position_id' => $peerSupervisorPosition->id,
        ]);

        $this->actingAs($peerSupervisor)
            ->from(route('overtime.index'))
            ->put(route('overtime.update', $overtime), [
                'status' => 'approved',
                'review_notes' => 'Only the assigned Reporting To SPV can approve.',
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->from(route('overtime.index'))
            ->put(route('overtime.update', $overtime), [
                'status' => 'approved',
                'review_notes' => 'Manager cannot skip the SPV step.',
            ])
            ->assertForbidden();

        $this->actingAs($supervisor)
            ->from(route('overtime.index'))
            ->put(route('overtime.update', $overtime), [
                'status' => 'approved',
                'review_notes' => 'Break cannot exceed submitted overtime.',
                'break_taken' => true,
                'break_hours' => 3,
            ])
            ->assertRedirect(route('overtime.index'))
            ->assertSessionHasErrors('break_hours');

        $this->actingAs($supervisor)
            ->from(route('overtime.index'))
            ->put(route('overtime.update', $overtime), [
                'status' => 'approved',
                'review_notes' => 'Approved by SPV.',
                'break_taken' => true,
                'break_hours' => 1,
            ])
            ->assertRedirect(route('overtime.index'))
            ->assertSessionHas('success');

        $overtime->refresh();
        $this->assertSame('pending_manager', $overtime->status);
        $this->assertSame($supervisor->id, $overtime->spv_reviewed_by);
        $this->assertSame(1.5, (float) $overtime->hours);
        $this->assertSame(1.0, (float) $overtime->break_hours);

        $approveResponse = $this
            ->actingAs($manager)
            ->from(route('overtime.index'))
            ->put(route('overtime.update', $overtime), [
                'status' => 'approved',
                'review_notes' => 'Approved for operational support.',
            ]);

        $approveResponse->assertRedirect(route('overtime.index'));
        $approveResponse->assertSessionHas('success');

        $overtime->refresh();
        $this->assertSame('approved', $overtime->status);
        $this->assertSame($manager->id, $overtime->reviewed_by);
        $this->assertSame($manager->id, $overtime->manager_reviewed_by);
    }

    public function test_ops_manager_can_approve_overtime_for_rsc_and_admin_loket(): void
    {
        $operations = $this->createDepartment(['name' => 'Operations', 'code' => 'OPS']);
        $managerPosition = $this->createPosition($operations, [
            'name' => 'Operations Manager',
            'code' => 'OPS-MGR',
            'is_manager' => true,
        ]);
        $manager = $this->createUser([
            'department' => $operations,
            'position' => $managerPosition,
        ], 'gmihr.attendance.overtime');
        $this->createEmployee($manager, [
            'department_id' => $operations->id,
            'position_id' => $managerPosition->id,
        ]);

        foreach ([
            ['name' => 'Risk Controll', 'code' => 'RSC'],
            ['name' => 'Admin Loket', 'code' => 'ADL'],
        ] as $departmentAttributes) {
            $department = $this->createDepartment($departmentAttributes);
            $requester = $this->createUser([
                'department' => $department,
            ], 'gmihr.attendance.overtime');
            $supervisorPosition = $this->createPosition($department, [
                'name' => $department->code.' Supervisor',
                'code' => $department->code.'-SPV',
            ]);
            $supervisor = $this->createUser([
                'department' => $department,
                'position' => $supervisorPosition,
            ], 'gmihr.attendance.overtime');
            $supervisorEmployee = $this->createEmployee($supervisor, [
                'department_id' => $department->id,
                'position_id' => $supervisorPosition->id,
            ]);
            $employee = $this->createEmployee($requester, [
                'department_id' => $department->id,
                'reports_to' => $supervisorEmployee->id,
            ]);

            $this->actingAs($requester)
                ->post(route('overtime.store'), [
                    'employee_id' => $employee->id,
                    'overtime_date' => now('Asia/Jakarta')->subDay()->toDateString(),
                    'start_time' => '18:00',
                    'end_time' => '20:00',
                    'reason' => 'Cross-department manager approval.',
                ])
                ->assertRedirect(route('overtime.index'))
                ->assertSessionHas('success');

            $overtime = Overtime::query()->latest('id')->firstOrFail();

            $this->actingAs($supervisor)
                ->from(route('overtime.index'))
                ->put(route('overtime.update', $overtime), [
                    'status' => 'approved',
                    'review_notes' => 'Approved by department supervisor.',
                ])
                ->assertRedirect(route('overtime.index'))
                ->assertSessionHas('success');

            $this->actingAs($manager)
                ->from(route('overtime.index'))
                ->put(route('overtime.update', $overtime), [
                    'status' => 'approved',
                    'review_notes' => 'Approved by OPS manager.',
                ])
                ->assertRedirect(route('overtime.index'))
                ->assertSessionHas('success');

            $this->assertSame('approved', $overtime->fresh()->status);
        }
    }

    public function test_admin_loket_overtime_uses_chandra_as_spv_approver(): void
    {
        $riskControl = $this->createDepartment(['name' => 'Risk Control', 'code' => 'RSC']);
        $adminLoket = $this->createDepartment(['name' => 'Admin Loket', 'code' => 'ADL']);
        $operations = $this->createDepartment(['name' => 'Operations', 'code' => 'OPS']);

        $chandraPosition = $this->createPosition($riskControl, [
            'name' => 'SPV Risk Control',
            'code' => 'RSC-SPV',
        ]);
        $chandra = $this->createUser([
            'name' => 'Chandra',
            'department' => $riskControl,
            'position' => $chandraPosition,
        ], 'gmihr.attendance.overtime');
        $chandraEmployee = $this->createEmployee($chandra, [
            'department_id' => $riskControl->id,
            'position_id' => $chandraPosition->id,
        ]);

        $adminLoketSpvPosition = $this->createPosition($adminLoket, [
            'name' => 'Admin Loket Supervisor',
            'code' => 'ADL-SPV',
        ]);
        $adminLoketSpv = $this->createUser([
            'department' => $adminLoket,
            'position' => $adminLoketSpvPosition,
        ], 'gmihr.attendance.overtime');
        $this->createEmployee($adminLoketSpv, [
            'department_id' => $adminLoket->id,
            'position_id' => $adminLoketSpvPosition->id,
        ]);

        $requester = $this->createUser([
            'department' => $adminLoket,
        ], 'gmihr.attendance.overtime');
        $employee = $this->createEmployee($requester, [
            'department_id' => $adminLoket->id,
            'reports_to' => $chandraEmployee->id,
        ]);

        $managerPosition = $this->createPosition($operations, [
            'name' => 'Operations Manager',
            'code' => 'OPS-MGR',
            'is_manager' => true,
        ]);
        $manager = $this->createUser([
            'department' => $operations,
            'position' => $managerPosition,
        ], 'gmihr.attendance.overtime');
        $this->createEmployee($manager, [
            'department_id' => $operations->id,
            'position_id' => $managerPosition->id,
        ]);

        $this->actingAs($requester)
            ->post(route('overtime.store'), [
                'employee_id' => $employee->id,
                'overtime_date' => now('Asia/Jakarta')->subDay()->toDateString(),
                'start_time' => '18:00',
                'end_time' => '20:00',
                'reason' => 'Admin Loket overtime approval routing.',
            ])
            ->assertRedirect(route('overtime.index'))
            ->assertSessionHas('success');

        $overtime = Overtime::query()->firstOrFail();

        $this->actingAs($chandra)
            ->get(route('overtime.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('overtimes.data', fn ($rows) => collect($rows)
                    ->contains(fn ($row) => $row['id'] === $overtime->id && $row['can_approve'])));
        $this->get(route('overtime.show', $overtime))->assertOk();

        $this->actingAs($adminLoketSpv)
            ->get(route('overtime.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('overtimes.data', fn ($rows) => ! collect($rows)->contains('id', $overtime->id)));
        $this->get(route('overtime.show', $overtime))->assertForbidden();

        $this->actingAs($adminLoketSpv)
            ->from(route('overtime.index'))
            ->put(route('overtime.update', $overtime), [
                'status' => 'approved',
                'review_notes' => 'The department Reporting To SPV is not assigned.',
            ])
            ->assertForbidden();

        $this->actingAs($chandra)
            ->from(route('overtime.index'))
            ->put(route('overtime.update', $overtime), [
                'status' => 'approved',
                'review_notes' => 'Approved by Chandra, SPV Risk Control.',
            ])
            ->assertRedirect(route('overtime.index'))
            ->assertSessionHas('success');

        $this->assertSame('pending_manager', $overtime->fresh()->status);
        $this->assertSame($chandra->id, $overtime->fresh()->spv_reviewed_by);
    }

    public function test_regular_user_cannot_submit_overtime_for_other_employee(): void
    {
        $department = $this->createDepartment([
            'name' => 'Operations',
            'code' => 'OPS',
        ]);
        $requester = $this->createUser([
            'department' => $department,
        ], 'gmihr.attendance.overtime');
        $this->createEmployee($requester, [
            'department_id' => $department->id,
            'name' => 'Requester Employee',
        ]);

        $otherUser = $this->createUser([
            'department' => $department,
        ], 'gmihr.attendance.overtime');
        $otherEmployee = $this->createEmployee($otherUser, [
            'department_id' => $department->id,
            'name' => 'Other Employee',
        ]);

        $response = $this
            ->actingAs($requester)
            ->from(route('overtime.create'))
            ->post(route('overtime.store'), [
                'employee_id' => $otherEmployee->id,
                'overtime_date' => now('Asia/Jakarta')->subDay()->toDateString(),
                'start_time' => '18:00',
                'end_time' => '19:00',
                'reason' => 'Trying to submit for another employee.',
            ]);

        $response->assertRedirect(route('overtime.create'));
        $response->assertSessionHasErrors('employee_id');

        $this->assertSame(0, Overtime::query()->count());
    }

    public function test_supervisor_can_approve_overtime_they_submitted_for_another_employee(): void
    {
        $department = $this->createDepartment(['name' => 'Operations', 'code' => 'OPS']);
        $supervisorPosition = $this->createPosition($department, [
            'name' => 'Operations Supervisor',
            'code' => 'OPS-SPV',
        ]);
        $supervisor = $this->createUser([
            'name' => 'Operations Supervisor',
            'department' => $department,
            'position' => $supervisorPosition,
        ], 'gmihr_attendance_overtime');
        $supervisorEmployee = $this->createEmployee($supervisor, [
            'department_id' => $department->id,
            'position_id' => $supervisorPosition->id,
        ]);

        $staff = $this->createUser([
            'name' => 'Operations Staff',
            'department' => $department,
        ], 'gmihr_attendance_overtime');
        $staffEmployee = $this->createEmployee($staff, [
            'department_id' => $department->id,
            'reports_to' => $supervisorEmployee->id,
        ]);

        $managerPosition = $this->createPosition($department, [
            'name' => 'Operations Manager',
            'code' => 'OPS-MGR',
            'is_manager' => true,
        ]);
        $manager = $this->createUser([
            'name' => 'Operations Manager',
            'department' => $department,
            'position' => $managerPosition,
        ], 'gmihr_attendance_overtime');
        $this->createEmployee($manager, [
            'department_id' => $department->id,
            'position_id' => $managerPosition->id,
        ]);

        $this->actingAs($supervisor)
            ->post(route('overtime.store'), [
                'employee_id' => $staffEmployee->id,
                'overtime_date' => now('Asia/Jakarta')->subDay()->toDateString(),
                'start_time' => '18:00',
                'end_time' => '20:00',
                'reason' => 'Overtime submitted by supervisor on behalf of staff.',
            ])
            ->assertRedirect(route('overtime.index'))
            ->assertSessionHas('success');

        $overtime = Overtime::query()->firstOrFail();
        $this->assertSame($supervisor->id, $overtime->user_id);
        $this->assertSame($staffEmployee->id, $overtime->employee_id);
        $this->assertSame('pending_spv', $overtime->status);

        $this->actingAs($supervisor)
            ->get(route('overtime.show', $overtime))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('canApprove', true));

        $this->actingAs($supervisor)
            ->put(route('overtime.update', $overtime), [
                'status' => 'approved',
                'review_notes' => 'Approved by assigned supervisor.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('pending_manager', $overtime->fresh()->status);
        $this->assertSame($supervisor->id, $overtime->fresh()->spv_reviewed_by);
    }

    public function test_mtc_and_it_employee_overtime_goes_directly_to_manager(): void
    {
        foreach ([
            ['name' => 'Maintanance', 'code' => 'MNT'],
            ['name' => 'Information Technology', 'code' => 'IT'],
        ] as $departmentAttributes) {
            $department = $this->createDepartment($departmentAttributes);
            $managerPosition = $this->createPosition($department, [
                'name' => $departmentAttributes['name'].' Manager',
                'code' => $departmentAttributes['code'].'-MGR',
                'is_manager' => true,
            ]);
            $manager = $this->createUser([
                'name' => $departmentAttributes['name'].' Manager',
                'department' => $department,
                'position' => $managerPosition,
            ], 'gmihr_attendance_overtime');
            $this->createEmployee($manager, [
                'department_id' => $department->id,
                'position_id' => $managerPosition->id,
            ]);

            $requester = $this->createUser([
                'name' => $departmentAttributes['name'].' Staff',
                'department' => $department,
            ], 'gmihr_attendance_overtime');
            $employee = $this->createEmployee($requester, [
                'department_id' => $department->id,
                'reports_to' => null,
            ]);

            $this->actingAs($requester)
                ->post(route('overtime.store'), [
                    'employee_id' => $employee->id,
                    'overtime_date' => now('Asia/Jakarta')->subDay()->toDateString(),
                    'start_time' => '18:00',
                    'end_time' => '20:00',
                    'reason' => $departmentAttributes['name'].' work after office hours.',
                ])
                ->assertRedirect(route('overtime.index'))
                ->assertSessionHas('success');

            $overtime = Overtime::query()->latest('id')->firstOrFail();
            $this->assertSame('pending_manager', $overtime->status);

            $this->actingAs($manager)
                ->from(route('overtime.index'))
                ->put(route('overtime.update', $overtime), [
                    'status' => 'approved',
                    'review_notes' => 'Approved by '.$departmentAttributes['name'].' Manager.',
                ])
                ->assertRedirect(route('overtime.index'))
                ->assertSessionHas('success');

            $overtime->refresh();
            $this->assertSame('approved', $overtime->status);
            $this->assertSame($manager->id, $overtime->manager_reviewed_by);
            $this->assertNull($overtime->spv_reviewed_by);
        }
    }

    public function test_andrian_can_view_overtime_requests_from_all_departments(): void
    {
        $fat = $this->createDepartment(['name' => 'FAT', 'code' => 'FAT']);
        $maintenance = $this->createDepartment(['name' => 'Maintanance', 'code' => 'MNT']);

        $andrian = $this->createManagerUser(
            ['name' => 'FAT', 'code' => 'FAT'],
            ['name' => 'CFO', 'code' => 'FAT-CFO', 'is_manager' => true],
            ['name' => 'Andrian', 'email' => 'Andrian@coldkeygmi.com'],
            'gmihr_attendance_overtime',
        );

        $fatEmployeeUser = $this->createUser(['department' => $fat], 'gmihr_attendance_overtime');
        $fatEmployee = $this->createEmployee($fatEmployeeUser, ['department_id' => $fat->id]);
        $maintenanceEmployeeUser = $this->createUser(['department' => $maintenance], 'gmihr_attendance_overtime');
        $maintenanceEmployee = $this->createEmployee($maintenanceEmployeeUser, ['department_id' => $maintenance->id]);
        $regularUser = $this->createUser(['department' => $fat], 'gmihr_attendance_overtime');
        $regularEmployee = $this->createEmployee($regularUser, [
            'department_id' => $fat->id,
        ]);

        $fatOvertime = $this->createOvertime($fatEmployeeUser, $fatEmployee);
        $maintenanceOvertime = $this->createOvertime($maintenanceEmployeeUser, $maintenanceEmployee);
        $regularOvertime = $this->createOvertime($regularUser, $regularEmployee);

        $this->actingAs($andrian)
            ->get(route('overtime.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('GMIHR/Overtime/Index')
                ->where('overtimes.data', fn ($rows) => collect($rows)->contains('id', $fatOvertime->id)
                    && collect($rows)->contains('id', $maintenanceOvertime->id)));

        $this->actingAs($regularUser)
            ->get(route('overtime.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('GMIHR/Overtime/Index')
                ->where('overtimes.data', fn ($rows) => collect($rows)->contains('id', $regularOvertime->id)
                    && ! collect($rows)->contains('id', $fatOvertime->id)
                    && ! collect($rows)->contains('id', $maintenanceOvertime->id)));
    }

    public function test_manager_and_supervisor_can_only_view_authorized_overtime(): void
    {
        $operations = $this->createDepartment(['name' => 'Operations', 'code' => 'OPS']);
        $finance = $this->createDepartment(['name' => 'Finance', 'code' => 'FIN']);
        $managerPosition = $this->createPosition($operations, [
            'name' => 'Operations Manager',
            'code' => 'OPS-MGR',
            'is_manager' => true,
        ]);
        $supervisorPosition = $this->createPosition($operations, [
            'name' => 'Operations Supervisor',
            'code' => 'OPS-SPV',
        ]);

        $manager = $this->createUser([
            'name' => 'Operations Manager',
            'department' => $operations,
            'position' => $managerPosition,
        ], 'gmihr_attendance_overtime');
        $supervisor = $this->createUser([
            'name' => 'Operations Supervisor',
            'department' => $operations,
            'position' => $supervisorPosition,
        ], 'gmihr_attendance_overtime');
        $supervisorEmployee = $this->createEmployee($supervisor, [
            'department_id' => $operations->id,
            'position_id' => $supervisorPosition->id,
        ]);

        $operationsUser = $this->createUser(['department' => $operations], 'gmihr_attendance_overtime');
        $operationsEmployee = $this->createEmployee($operationsUser, [
            'department_id' => $operations->id,
            'reports_to' => $supervisorEmployee->id,
        ]);
        $financeUser = $this->createUser(['department' => $finance], 'gmihr_attendance_overtime');
        $financeEmployee = $this->createEmployee($financeUser, ['department_id' => $finance->id]);
        $operationsOvertime = $this->createOvertime($operationsUser, $operationsEmployee);
        $financeOvertime = $this->createOvertime($financeUser, $financeEmployee);

        foreach ([$manager, $supervisor] as $viewer) {
            $this->actingAs($viewer)
                ->get(route('overtime.index'))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('GMIHR/Overtime/Index')
                    ->where('overtimes.data', fn ($rows) => collect($rows)->contains('id', $operationsOvertime->id)
                        && ! collect($rows)->contains('id', $financeOvertime->id)));

            $this->get(route('overtime.show', $operationsOvertime))->assertOk();
            $this->get(route('overtime.show', $financeOvertime))->assertForbidden();

            $this->get(route('overtime.create'))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->where('employees', fn ($employees) => collect($employees)->pluck('id')->all() === [$operationsEmployee->id]));
        }
    }

    private function createOvertime(\App\Models\User $user, \App\Models\Employee $employee): Overtime
    {
        return Overtime::create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'overtime_date' => now()->toDateString(),
            'start_time' => '18:00',
            'end_time' => '19:00',
            'hours' => 1,
            'reason' => 'Cross-department overtime visibility test.',
            'status' => 'pending',
        ]);
    }
}
