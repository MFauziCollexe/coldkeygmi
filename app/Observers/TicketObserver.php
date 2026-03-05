<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TicketObserver
{
    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {
        ActivityLog::create([
            'table_name' => 'tickets',
            'record_id' => $ticket->id,
            'action' => 'insert',
            'old_values' => null,
            'new_values' => $ticket->toArray(),
            'user_id' => Auth::id() ?? 'system',
            'user_email' => Auth::user()?->email ?? null,
            'ip_address' => request()?->ip() ?? null,
            'description' => "Ticket {$ticket->ticket_number} created.",
        ]);
        Log::info('Ticket created', ['ticket' => $ticket->id, 'user' => Auth::id()]);
    }

    /**
     * Handle the Ticket "updated" event.
     */
    public function updated(Ticket $ticket): void
    {
        ActivityLog::create([
            'table_name' => 'tickets',
            'record_id' => $ticket->id,
            'action' => 'update',
            'old_values' => null,
            'new_values' => $ticket->toArray(),
            'user_id' => Auth::id() ?? 'system',
            'user_email' => Auth::user()?->email ?? null,
            'ip_address' => request()?->ip() ?? null,
            'description' => "Ticket {$ticket->ticket_number} updated.",
        ]);
        Log::info('Ticket updated', ['ticket' => $ticket->id, 'user' => Auth::id()]);
    }

    /**
     * Handle the Ticket "deleted" event.
     */
    public function deleted(Ticket $ticket): void
    {
        ActivityLog::create([
            'table_name' => 'tickets',
            'record_id' => $ticket->id,
            'action' => 'delete',
            'old_values' => $ticket->toArray(),
            'new_values' => null,
            'user_id' => Auth::id() ?? 'system',
            'user_email' => Auth::user()?->email ?? null,
            'ip_address' => request()?->ip() ?? null,
            'description' => "Ticket {$ticket->ticket_number} deleted.",
        ]);
        Log::info('Ticket deleted', ['ticket' => $ticket->id, 'user' => Auth::id()]);
    }

    /**
     * Handle the Ticket "restored" event.
     */
    public function restored(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "force deleted" event.
     */
    public function forceDeleted(Ticket $ticket): void
    {
        //
    }
}
