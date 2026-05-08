<?php

namespace Tests\Feature\Checklist;

use App\Models\ChecklistHeader;
use App\Models\ChecklistState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_user_can_save_non_warehouse_sanitation_draft(): void
    {
        $itDepartment = $this->createDepartment([
            'name' => 'Information Technology',
            'code' => 'IT',
        ]);
        $itUser = $this->createUser([
            'department' => $itDepartment,
        ], 'gmiic.checklist');

        $response = $this
            ->actingAs($itUser)
            ->postJson(route('gmiic.checklist.entries.save'), [
                'entry' => [
                    'id' => 'NWS-001',
                    'template_id' => 'non_warehouse_sanitation',
                    'name' => 'Non Warehouse Sanitation',
                    'form' => [
                        'date' => '2026-05-08',
                    ],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonPath('entry.id', 'NWS-001');
        $response->assertJsonPath('entry.template_id', 'non_warehouse_sanitation');

        $header = ChecklistHeader::query()->firstOrFail();

        $this->assertDatabaseHas('checklist_templates', [
            'code' => 'non_warehouse_sanitation',
            'module' => 'gmiic.checklist',
        ]);
        $this->assertSame('draft', $header->status);
        $this->assertSame($itUser->id, $header->created_by);
        $this->assertNull($header->approved_by);
        $this->assertSame(1, ChecklistState::query()->where('checklist_header_id', $header->id)->count());
    }

    public function test_hse_user_can_approve_allowed_template_and_unauthorized_user_is_forbidden(): void
    {
        $securityDepartment = $this->createDepartment([
            'name' => 'Security',
            'code' => 'SEC',
        ]);
        $securityUser = $this->createUser([
            'department' => $securityDepartment,
        ], 'gmiic.checklist');

        $forbiddenResponse = $this
            ->actingAs($securityUser)
            ->postJson(route('gmiic.checklist.entries.save'), [
                'entry' => [
                    'id' => 'NWS-002',
                    'template_id' => 'non_warehouse_sanitation',
                    'name' => 'Non Warehouse Sanitation',
                    'form' => [
                        'approved' => true,
                    ],
                ],
                'approval_action' => true,
            ]);

        $forbiddenResponse->assertForbidden();

        $hseDepartment = $this->createDepartment([
            'name' => 'HSE',
            'code' => 'HSE',
        ]);
        $hseUser = $this->createUser([
            'department' => $hseDepartment,
        ], 'gmiic.checklist');

        $approvedResponse = $this
            ->actingAs($hseUser)
            ->postJson(route('gmiic.checklist.entries.save'), [
                'entry' => [
                    'id' => 'NWS-003',
                    'template_id' => 'non_warehouse_sanitation',
                    'name' => 'Non Warehouse Sanitation',
                    'form' => [
                        'approved' => true,
                        'submitted_days' => ['2026-05-08'],
                    ],
                ],
                'approval_action' => true,
            ]);

        $approvedResponse->assertOk();

        $header = ChecklistHeader::query()
            ->where('entry_code', 'NWS-003')
            ->firstOrFail();

        $this->assertSame('approved', $header->status);
        $this->assertSame($hseUser->id, $header->approved_by);
        $this->assertNotNull($header->approved_at);
    }
}
