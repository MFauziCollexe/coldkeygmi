<?php

namespace Tests\Feature\VisitorForm;

use App\Models\VisitorForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisitorFormWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_form_requires_both_approvals_before_status_can_change(): void
    {
        Storage::fake('public');

        $requester = $this->createUser([], 'gmi_visitor_permit.visitor_form');
        $hostUser = $this->createUser([], 'gmi_visitor_permit.visitor_form');
        $securityDepartment = $this->createDepartment([
            'name' => 'Security',
            'code' => 'SEC',
        ]);
        $securityUser = $this->createUser([
            'department' => $securityDepartment,
        ], 'gmi_visitor_permit.visitor_form');

        $storeResponse = $this
            ->actingAs($requester)
            ->post(route('gmi-visitor-permit.visitor-form.store'), [
                'visitor_name' => 'Andi Tamu',
                'from' => 'PT Vendor',
                'identity_no' => 'ID-001',
                'purpose' => 'Meeting with host user',
                'host_user_id' => $hostUser->id,
                'visit_date' => '2026-05-08',
                'appointment_time' => '09:00',
                'attachments' => [
                    UploadedFile::fake()->image('visitor-badge.jpg'),
                ],
            ]);

        $storeResponse->assertRedirect(route('gmi-visitor-permit.visitor-form.index'));
        $storeResponse->assertSessionHas('success');

        $visitorForm = VisitorForm::query()->firstOrFail();
        $this->assertSame('pending', $visitorForm->approval_status);
        $this->assertSame('Waiting', $visitorForm->status);
        $this->assertDatabaseHas('visitor_form_attachments', [
            'visitor_form_id' => $visitorForm->id,
            'filename' => 'visitor-badge.jpg',
        ]);

        $blockedStatusResponse = $this
            ->actingAs($requester)
            ->from(route('gmi-visitor-permit.visitor-form.index'))
            ->post(route('gmi-visitor-permit.visitor-form.update-status', $visitorForm), [
                'status' => 'Checked In',
            ]);

        $blockedStatusResponse->assertRedirect(route('gmi-visitor-permit.visitor-form.index'));
        $blockedStatusResponse->assertSessionHasErrors('status');

        $this
            ->actingAs($securityUser)
            ->from(route('gmi-visitor-permit.visitor-form.index'))
            ->post(route('gmi-visitor-permit.visitor-form.approve', $visitorForm), [
                'role' => 'security',
            ])
            ->assertRedirect(route('gmi-visitor-permit.visitor-form.index'));

        $visitorForm->refresh();
        $this->assertSame('partially_approved', $visitorForm->approval_status);

        $this
            ->actingAs($hostUser)
            ->from(route('gmi-visitor-permit.visitor-form.index'))
            ->post(route('gmi-visitor-permit.visitor-form.approve', $visitorForm), [
                'role' => 'host',
            ])
            ->assertRedirect(route('gmi-visitor-permit.visitor-form.index'));

        $visitorForm->refresh();
        $this->assertSame('approved', $visitorForm->approval_status);

        $statusResponse = $this
            ->actingAs($requester)
            ->from(route('gmi-visitor-permit.visitor-form.index'))
            ->post(route('gmi-visitor-permit.visitor-form.update-status', $visitorForm), [
                'status' => 'Checked In',
            ]);

        $statusResponse->assertRedirect(route('gmi-visitor-permit.visitor-form.index'));
        $statusResponse->assertSessionHas('success');

        $visitorForm->refresh();
        $this->assertSame('Checked In', $visitorForm->status);
    }

    public function test_non_security_user_cannot_approve_visitor_form_as_security(): void
    {
        $requester = $this->createUser([], 'gmi_visitor_permit.visitor_form');
        $hostUser = $this->createUser([], 'gmi_visitor_permit.visitor_form');
        $nonSecurityApprover = $this->createUser([], 'gmi_visitor_permit.visitor_form');

        $visitorForm = VisitorForm::create([
            'visitor_name' => 'Budi Tamu',
            'from' => 'PT Vendor',
            'identity_no' => 'ID-002',
            'purpose' => 'Site visit discussion',
            'host_name' => $hostUser->name,
            'host_user_id' => $hostUser->id,
            'visit_date' => '2026-05-08',
            'appointment_time' => '10:00:00',
            'status' => 'Waiting',
            'approval_status' => 'pending',
            'user_id' => $requester->id,
        ]);

        $response = $this
            ->actingAs($nonSecurityApprover)
            ->post(route('gmi-visitor-permit.visitor-form.approve', $visitorForm), [
                'role' => 'security',
            ]);

        $response->assertForbidden();

        $visitorForm->refresh();
        $this->assertNull($visitorForm->security_approved_at);
        $this->assertSame('pending', $visitorForm->approval_status);
    }
}
