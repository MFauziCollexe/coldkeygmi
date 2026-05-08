<?php

namespace Tests\Feature\Tickets;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_ticket_with_attachment(): void
    {
        Storage::fake('public');

        $department = $this->createDepartment([
            'name' => 'Utility',
            'code' => 'UTILITY-' . strtoupper(fake()->unique()->lexify('??')),
        ]);
        $creator = $this->createUser(['department' => $department], 'utility.tickets');
        $assignee = $this->createUser(['department' => $department], 'utility.tickets');

        $response = $this
            ->actingAs($creator)
            ->post(route('tickets.store'), [
                'title' => 'Leak inspection',
                'description' => 'Need to inspect the utility pipe immediately.',
                'deadline' => now()->addDay()->toDateString(),
                'department_id' => $department->id,
                'assigned_to' => $assignee->id,
                'attachments' => [
                    UploadedFile::fake()->image('evidence.jpg'),
                ],
            ]);

        $response->assertRedirect(route('tickets.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tickets', [
            'title' => 'Leak inspection',
            'created_by' => $creator->id,
            'assigned_to' => $assignee->id,
            'department_id' => $department->id,
            'status' => 'Open',
        ]);

        $ticket = Ticket::query()->firstOrFail();

        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'filename' => 'evidence.jpg',
        ]);
        $attachmentPath = $ticket->attachments()->value('path');
        $this->assertNotNull($attachmentPath);
        Storage::disk('public')->assertExists($attachmentPath);
    }

    public function test_assignee_can_resolve_ticket_and_creator_can_close_it(): void
    {
        $department = $this->createDepartment([
            'name' => 'Operations',
            'code' => 'OPS-' . strtoupper(fake()->unique()->lexify('??')),
        ]);
        $creator = $this->createUser(['department' => $department], 'utility.tickets');
        $assignee = $this->createUser(['department' => $department], 'utility.tickets');
        $ticket = $this->createTicket($department, $creator, $assignee, [
            'status' => 'In Progress',
        ]);

        $resolveResponse = $this
            ->actingAs($assignee)
            ->post(route('tickets.resolve', $ticket), [
                'resolution_notes' => 'Issue isolated, repaired, and tested successfully.',
            ]);

        $resolveResponse->assertRedirect();
        $resolveResponse->assertSessionHas('success');

        $ticket->refresh();
        $this->assertSame('Resolved', $ticket->status);
        $this->assertSame($assignee->id, $ticket->resolved_by);
        $this->assertNotNull($ticket->resolved_at);
        $this->assertSame(now()->toDateString(), optional($ticket->resolve_deadline)->toDateString());

        $closeResponse = $this
            ->actingAs($creator)
            ->post(route('tickets.close', $ticket));

        $closeResponse->assertRedirect();
        $closeResponse->assertSessionHas('success');

        $ticket->refresh();
        $this->assertSame('Closed', $ticket->status);
        $this->assertSame($creator->id, $ticket->closed_by);
        $this->assertNotNull($ticket->closed_at);
    }

    public function test_creator_cannot_change_open_ticket_status_when_not_assigned(): void
    {
        $department = $this->createDepartment([
            'name' => 'Maintenance',
            'code' => 'MNT-' . strtoupper(fake()->unique()->lexify('??')),
        ]);
        $creator = $this->createUser(['department' => $department], 'utility.tickets');
        $assignee = $this->createUser(['department' => $department], 'utility.tickets');
        $ticket = $this->createTicket($department, $creator, $assignee, [
            'status' => 'Open',
        ]);

        $response = $this
            ->actingAs($creator)
            ->post(route('tickets.update-status', $ticket), [
                'status' => 'In Progress',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('status');

        $ticket->refresh();
        $this->assertSame('Open', $ticket->status);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTicket(Department $department, User $creator, User $assignee, array $attributes = []): Ticket
    {
        return Ticket::create(array_merge([
            'ticket_number' => 'TKT-' . strtoupper(fake()->bothify('####??##')),
            'title' => 'Utility incident',
            'description' => 'Generated by automated ticket workflow test.',
            'deadline' => now()->addDays(2)->toDateString(),
            'deadline_approved' => true,
            'status' => 'Open',
            'created_by' => $creator->id,
            'assigned_to' => $assignee->id,
            'department_id' => $department->id,
        ], $attributes));
    }
}
