<?php

namespace Tests\Feature\Overtime;

use App\Models\Overtime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OvertimeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_overtime_for_self_and_manager_can_approve_it(): void
    {
        $department = $this->createDepartment([
            'name' => 'Operations',
            'code' => 'OPS',
        ]);
        $requester = $this->createUser([
            'department' => $department,
        ], 'gmihr.attendance.overtime');
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
        ], 'gmihr.attendance.overtime');

        $storeResponse = $this
            ->actingAs($requester)
            ->post(route('overtime.store'), [
                'employee_id' => $employee->id,
                'overtime_date' => '2026-05-10',
                'start_time' => '18:00',
                'end_time' => '20:30',
                'reason' => 'Support deployment after office hours.',
            ]);

        $storeResponse->assertRedirect(route('overtime.index'));
        $storeResponse->assertSessionHas('success');

        $overtime = Overtime::query()->firstOrFail();
        $this->assertSame('pending', $overtime->status);
        $this->assertSame(2.5, (float) $overtime->hours);
        $this->assertSame($employee->id, $overtime->employee_id);

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
                'overtime_date' => '2026-05-10',
                'start_time' => '18:00',
                'end_time' => '19:00',
                'reason' => 'Trying to submit for another employee.',
            ]);

        $response->assertRedirect(route('overtime.create'));
        $response->assertSessionHasErrors('employee_id');

        $this->assertSame(0, Overtime::query()->count());
    }
}
