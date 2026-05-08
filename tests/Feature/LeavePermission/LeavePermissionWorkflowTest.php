<?php

namespace Tests\Feature\LeavePermission;

use App\Models\LeavePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavePermissionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_leave_permission_for_self_and_manager_can_approve_it(): void
    {
        $department = $this->createDepartment([
            'name' => 'Operations',
            'code' => 'OPS',
        ]);
        $requester = $this->createUser([
            'department' => $department,
        ], 'gmihr.attendance.leave_permission');
        $employee = $this->createEmployee($requester, [
            'department_id' => $department->id,
            'name' => 'Requester Employee',
        ]);

        $managerPosition = $this->createPosition($department, [
            'name' => 'Operations Manager',
            'code' => 'OPS-MGR',
            'is_manager' => true,
        ]);
        $manager = $this->createUser([
            'department' => $department,
            'position' => $managerPosition,
        ], 'gmihr.attendance.leave_permission');

        $storeResponse = $this
            ->actingAs($requester)
            ->post(route('leave-permission.store'), [
                'employee_id' => $employee->id,
                'type' => 'cuti',
                'start_date' => '2026-05-10',
                'end_date' => '2026-05-12',
                'reason' => 'Annual leave for family event.',
            ]);

        $storeResponse->assertRedirect(route('leave-permission.index'));
        $storeResponse->assertSessionHas('success');

        $leavePermission = LeavePermission::query()->firstOrFail();
        $this->assertSame('pending', $leavePermission->status);
        $this->assertSame(3, $leavePermission->days);
        $this->assertSame($employee->id, $leavePermission->employee_id);

        $approveResponse = $this
            ->actingAs($manager)
            ->from(route('leave-permission.index'))
            ->put(route('leave-permission.update', $leavePermission), [
                'status' => 'approved',
                'review_notes' => 'Approved by operations manager.',
            ]);

        $approveResponse->assertRedirect(route('leave-permission.index'));
        $approveResponse->assertSessionHas('success');

        $leavePermission->refresh();
        $this->assertSame('approved', $leavePermission->status);
        $this->assertSame($manager->id, $leavePermission->reviewed_by);
    }

    public function test_regular_user_cannot_submit_leave_permission_for_other_employee(): void
    {
        $department = $this->createDepartment([
            'name' => 'Operations',
            'code' => 'OPS',
        ]);
        $requester = $this->createUser([
            'department' => $department,
        ], 'gmihr.attendance.leave_permission');
        $this->createEmployee($requester, [
            'department_id' => $department->id,
            'name' => 'Requester Employee',
        ]);

        $otherUser = $this->createUser([
            'department' => $department,
        ], 'gmihr.attendance.leave_permission');
        $otherEmployee = $this->createEmployee($otherUser, [
            'department_id' => $department->id,
            'name' => 'Other Employee',
        ]);

        $response = $this
            ->actingAs($requester)
            ->from(route('leave-permission.create'))
            ->post(route('leave-permission.store'), [
                'employee_id' => $otherEmployee->id,
                'type' => 'izin',
                'start_date' => '2026-05-10',
                'end_date' => '2026-05-10',
                'reason' => 'Trying to submit for another employee.',
            ]);

        $response->assertRedirect(route('leave-permission.create'));
        $response->assertSessionHasErrors('employee_id');

        $this->assertSame(0, LeavePermission::query()->count());
    }
}
