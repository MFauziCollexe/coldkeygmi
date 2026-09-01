<?php

namespace Tests\Feature\LeavePermission;

use App\Models\LeavePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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

    private function createCfoUser(): User
    {
        $finance = $this->createDepartment(['name' => 'Finance', 'code' => 'FIN']);
        $cfoPosition = $this->createPosition($finance, [
            'name' => 'Chief Financial Officer',
            'code' => 'CFO',
            'is_manager' => true,
        ]);

        return $this->createUser([
            'department' => $finance,
            'position' => $cfoPosition,
        ], 'gmihr.attendance.leave_permission');
    }

    public function test_manager_request_is_approved_by_cfo_not_by_other_manager(): void
    {
        $department = $this->createDepartment(['name' => 'Operations', 'code' => 'OPS']);
        $manager = $this->createManagerUser(
            ['name' => 'Operations', 'code' => 'OPS'],
            ['name' => 'Operations Manager', 'code' => 'OPS-MGR', 'is_manager' => true],
            [],
            'gmihr.attendance.leave_permission'
        );
        $this->createEmployee($manager, [
            'department_id' => $department->id,
            'name' => 'Manager Employee',
        ]);

        $storeResponse = $this
            ->actingAs($manager)
            ->post(route('leave-permission.store'), [
                'employee_id' => $manager->employee->id,
                'type' => 'cuti',
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-03',
                'reason' => 'Manager annual leave.',
            ]);
        $storeResponse->assertRedirect(route('leave-permission.index'));

        $leavePermission = LeavePermission::query()->firstOrFail();
        $this->assertSame($manager->id, $leavePermission->user_id);

        $otherManager = $this->createManagerUser(
            ['name' => 'Sales', 'code' => 'SLS'],
            ['name' => 'Sales Manager', 'code' => 'SLS-MGR', 'is_manager' => true],
            [],
            'gmihr.attendance.leave_permission'
        );

        $rejectByManager = $this
            ->actingAs($otherManager)
            ->from(route('leave-permission.index'))
            ->put(route('leave-permission.update', $leavePermission), [
                'status' => 'approved',
                'review_notes' => 'Approved by another manager.',
            ]);
        $rejectByManager->assertStatus(403);

        $cfo = $this->createCfoUser();
        $approveByCfo = $this
            ->actingAs($cfo)
            ->from(route('leave-permission.index'))
            ->put(route('leave-permission.update', $leavePermission), [
                'status' => 'approved',
                'review_notes' => 'Approved by CFO.',
            ]);
        $approveByCfo->assertRedirect(route('leave-permission.index'));

        $leavePermission->refresh();
        $this->assertSame('approved', $leavePermission->status);
        $this->assertSame($cfo->id, $leavePermission->reviewed_by);
    }

    public function test_manager_cannot_approve_their_own_request(): void
    {
        $department = $this->createDepartment(['name' => 'Operations', 'code' => 'OPS']);
        $manager = $this->createManagerUser(
            ['name' => 'Operations', 'code' => 'OPS'],
            ['name' => 'Operations Manager', 'code' => 'OPS-MGR', 'is_manager' => true],
            [],
            'gmihr.attendance.leave_permission'
        );
        $this->createEmployee($manager, [
            'department_id' => $department->id,
            'name' => 'Manager Employee',
        ]);

        $storeResponse = $this
            ->actingAs($manager)
            ->post(route('leave-permission.store'), [
                'employee_id' => $manager->employee->id,
                'type' => 'izin',
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-01',
                'reason' => 'Manager personal errand.',
            ]);
        $storeResponse->assertRedirect(route('leave-permission.index'));

        $leavePermission = LeavePermission::query()->firstOrFail();

        $selfApprove = $this
            ->actingAs($manager)
            ->from(route('leave-permission.index'))
            ->put(route('leave-permission.update', $leavePermission), [
                'status' => 'approved',
                'review_notes' => 'Self approval attempt.',
            ]);
        $selfApprove->assertStatus(403);

        $leavePermission->refresh();
        $this->assertSame('pending', $leavePermission->status);
    }

    public function test_detail_hides_review_actions_for_manager_own_request(): void
    {
        $manager = $this->createManagerUser(
            ['name' => 'Operations', 'code' => 'OPS'],
            ['name' => 'Operations Manager', 'code' => 'OPS-MGR', 'is_manager' => true],
            [],
            'gmihr.attendance.leave_permission'
        );
        $employee = $this->createEmployee($manager);
        $leavePermission = LeavePermission::create([
            'user_id' => $manager->id,
            'employee_id' => $employee->id,
            'type' => 'cuti',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-01',
            'days' => 1,
            'reason' => 'Manager own request.',
            'status' => 'pending',
        ]);

        $this
            ->actingAs($manager)
            ->get(route('leave-permission.show', $leavePermission))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('GMIHR/LeavePermission/Show')
                ->where('canReview', false)
            );
    }

    public function test_detail_shows_review_actions_for_staff_request_under_manager(): void
    {
        $department = $this->createDepartment(['name' => 'Operations', 'code' => 'OPS']);
        $requester = $this->createUser([
            'department' => $department,
        ], 'gmihr.attendance.leave_permission');
        $employee = $this->createEmployee($requester);
        $manager = $this->createManagerUser(
            ['name' => 'Operations', 'code' => 'OPS'],
            ['name' => 'Operations Manager', 'code' => 'OPS-MGR', 'is_manager' => true],
            [],
            'gmihr.attendance.leave_permission'
        );
        $leavePermission = LeavePermission::create([
            'user_id' => $requester->id,
            'employee_id' => $employee->id,
            'type' => 'izin',
            'start_date' => '2026-08-02',
            'end_date' => '2026-08-02',
            'days' => 1,
            'reason' => 'Staff permission request.',
            'status' => 'pending',
        ]);

        $this
            ->actingAs($manager)
            ->get(route('leave-permission.show', $leavePermission))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('GMIHR/LeavePermission/Show')
                ->where('canReview', true)
            );
    }

    public function test_detail_shows_review_actions_for_cfo_on_manager_request(): void
    {
        $manager = $this->createManagerUser(
            ['name' => 'Operations', 'code' => 'OPS'],
            ['name' => 'Operations Manager', 'code' => 'OPS-MGR', 'is_manager' => true],
            [],
            'gmihr.attendance.leave_permission'
        );
        $employee = $this->createEmployee($manager);
        $leavePermission = LeavePermission::create([
            'user_id' => $manager->id,
            'employee_id' => $employee->id,
            'type' => 'cuti',
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-03',
            'days' => 1,
            'reason' => 'Manager request for CFO review.',
            'status' => 'pending',
        ]);
        $cfo = $this->createCfoUser();

        $this
            ->actingAs($cfo)
            ->get(route('leave-permission.show', $leavePermission))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('GMIHR/LeavePermission/Show')
                ->where('canReview', true)
            );
    }
}
