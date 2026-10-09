<?php

namespace App\Console\Commands;

use App\Services\TicketService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Runs every five minutes (routes/console.php): an unpaid ticket can never be used once
 * its train has left, so it is cancelled together with any unfinished payment.
 */
#[Signature('tickets:expire-unpaid')]
#[Description('Cancel unpaid tickets whose train has already left')]
class ExpireUnpaidTickets extends Command
{
    public function handle(TicketService $tickets): int
    {
        $this->info("Cancelled {$tickets->cancelUnpaidDeparted()} unpaid ticket(s).");

        return self::SUCCESS;
    }
}
