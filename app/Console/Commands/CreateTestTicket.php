<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\User;

class CreateTestTicket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:test-ticket {--assignee= : user id to assign}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test ticket for E2E verification';

    public function handle()
    {
        $user = User::first();
        if (!$user) {
            $this->error('No users found');
            return 1;
        }

        $assignee = $this->option('assignee') ?: $user->id;

        $ticket = Ticket::create([
            'ticket_number' => 'TKT-E2E-' . strtoupper(substr(uniqid(), -6)),
            'title' => 'E2E Test Ticket',
            'description' => 'Created by artisan command for E2E test',
            'priority' => 'Medium',
            'status' => 'Open',
            'created_by' => $user->id,
            'assigned_to' => $assignee,
        ]);

        $this->info('Created ticket id: ' . $ticket->id . ' number: ' . $ticket->ticket_number);
        return 0;
    }
}
