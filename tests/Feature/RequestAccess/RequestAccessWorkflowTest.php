<?php

namespace Tests\Feature\RequestAccess;

use App\Models\RequestAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestAccessWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_approve_and_it_can_process_existing_user_request(): void
    {
        $opsDepartment = $this->createDepartment([
            'name' => 'Operations',
            'code' => 'OPS',
        ]);
        $requester = $this->createUser([
            'department' => $opsDepartment,
        ], 'utility.request_access');
        $targetUser = $this->createUser([
            'department' => $opsDepartment,
        ]);

        $managerPosition = $this->createPosition($opsDepartment, [
            'name' => 'Operations Manager',
            'code' => 'OPS-MGR',
            'is_manager' => true,
        ]);
        $manager = $this->createUser([
            'department' => $opsDepartment,
            'position' => $managerPosition,
        ], 'utility.request_access');

        $itDepartment = $this->createDepartment([
            'name' => 'Information Technology',
            'code' => 'IT',
        ]);
        $itPosition = $this->createPosition($itDepartment, [
            'name' => 'IT Staff',
            'code' => 'IT-STAFF',
        ]);
        $itUser = $this->createUser([
            'department' => $itDepartment,
            'position' => $itPosition,
        ], 'utility.request_access');

        $storeResponse = $this
            ->actingAs($requester)
            ->post(route('request-access.store'), [
                'type' => 'existing_user',
                'user_id' => $targetUser->id,
                'module_keys' => ['utility.tickets', 'utility.stock_card'],
                'reason' => 'Need access to support daily operational requests.',
            ]);

        $storeResponse->assertRedirect(route('request-access.index'));
        $storeResponse->assertSessionHas('success');

        $requestAccess = RequestAccess::query()->firstOrFail();
        $this->assertSame('pending', $requestAccess->status);
        $this->assertSame($requester->id, $requestAccess->created_by);

        $approveResponse = $this
            ->actingAs($manager)
            ->from(route('request-access.index'))
            ->post(route('request-access.approve', $requestAccess), [
                'review_notes' => 'Department manager approved the requested modules.',
            ]);

        $approveResponse->assertRedirect(route('request-access.index'));
        $approveResponse->assertSessionHas('success');

        $requestAccess->refresh();
        $this->assertSame('approved', $requestAccess->status);
        $this->assertSame($manager->id, $requestAccess->reviewed_by);

        $processResponse = $this
            ->actingAs($itUser)
            ->from(route('request-access.index'))
            ->post(route('request-access.process', $requestAccess), [
                'processing_notes' => 'Granted permissions for the requested modules.',
            ]);

        $processResponse->assertRedirect(route('request-access.index'));
        $processResponse->assertSessionHas('success');

        $requestAccess->refresh();
        $this->assertSame('processed', $requestAccess->status);
        $this->assertSame($itUser->id, $requestAccess->processed_by);

        $this->assertDatabaseHas('module_permissions', [
            'user_id' => $targetUser->id,
            'module_key' => 'utility.tickets',
        ]);
        $this->assertDatabaseHas('module_permissions', [
            'user_id' => $targetUser->id,
            'module_key' => 'utility.stock_card',
        ]);
    }

    public function test_non_manager_cannot_approve_request_access(): void
    {
        $department = $this->createDepartment([
            'name' => 'Operations',
            'code' => 'OPS',
        ]);
        $requester = $this->createUser([
            'department' => $department,
        ], 'utility.request_access');
        $reviewer = $this->createUser([
            'department' => $department,
        ], 'utility.request_access');

        $requestAccess = RequestAccess::create([
            'request_number' => RequestAccess::generateRequestNumber(),
            'type' => 'new_user',
            'target_user_name' => 'New Utility Staff',
            'target_user_email' => 'new.utility.staff@example.com',
            'target_department_id' => $department->id,
            'module_keys' => ['utility.tickets'],
            'reason' => 'Need access for a new utility operator.',
            'status' => 'pending',
            'created_by' => $requester->id,
        ]);

        $response = $this
            ->actingAs($reviewer)
            ->from(route('request-access.index'))
            ->post(route('request-access.approve', $requestAccess), [
                'review_notes' => 'Trying to approve without manager role.',
            ]);

        $response->assertRedirect(route('request-access.index'));
        $response->assertSessionHasErrors('error');

        $requestAccess->refresh();
        $this->assertSame('pending', $requestAccess->status);
        $this->assertNull($requestAccess->reviewed_by);
        $this->assertDatabaseMissing('module_permissions', [
            'user_id' => $requester->id,
            'module_key' => 'utility.tickets',
        ]);
    }
}
