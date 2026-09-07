<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

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
            'price' => 'required|numeric',
            'currency' => 'required|string'
        ]);

        $ticket = Ticket::create([
            'user_id' => $request->user()->id,
            'journey_id' => $data['journey_id'],
            'price' => $data['price'],
            'currency' => $data['currency'],
            'status' => 'paid',
            'purchased_at' => now()
        ]);

        return response()->json($ticket, 201);
    }
}
