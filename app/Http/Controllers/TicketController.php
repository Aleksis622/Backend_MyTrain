<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    
    public function index(Request $request)
    {
        return Ticket::where('user_id', $request->user()->id)
            ->with(['journey.train'])
            ->paginate(20);
    }

  
    public function store(Request $request)
    {
        $data = $request->validate([
            'journey_id' => 'required|exists:journeys,id',
            'price'      => 'required|numeric|min:0',
            'currency'   => 'required|string|in:EUR,USD,GBP',
        ]);

        
        $ticketCode = strtoupper(Str::random(10)); 

        $ticket = Ticket::create([
            'user_id'      => $request->user()->id,
            'journey_id'   => $data['journey_id'],
            'price'        => $data['price'],
            'currency'     => $data['currency'],
            'ticket_code'  => $ticketCode,
            'status'       => 'confirmed', 
            'purchased_at' => now(),
        ]);

        return response()->json([
            'message' => 'Ticket successfully created',
            'ticket'  => $ticket,
        ], 201);
    }

    
    public function markPaid(Ticket $ticket)
    {
        $ticket->status = 'paid'; 
        $ticket->save();

        return response()->json([
            'message' => 'Ticket marked as paid',
            'ticket'  => $ticket,
        ]);
    }

    
    public function cancel(Ticket $ticket)
    {
        $ticket->status = 'cancelled';
        $ticket->save();

        return response()->json([
            'message' => 'Ticket cancelled',
            'ticket'  => $ticket,
        ]);
    }
}

