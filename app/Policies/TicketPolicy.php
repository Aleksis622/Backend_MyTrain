<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Users may only see, pay for, or cancel their own tickets.
     */
    public function manage(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id;
    }
}
