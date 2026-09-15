<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
   
    public function index(Request $request)
    {
        return Payment::where('user_id', $request->user()->id)
            ->with(['ticket'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

   
    public function show($id)
    {
        return Payment::with(['ticket', 'user'])
            ->findOrFail($id);
    }

    
    public function store(Request $request)
    {
        $data = $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'provider'  => 'required|string',
        ]);

        $ticket = Ticket::findOrFail($data['ticket_id']);

        $payment = Payment::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $request->user()->id,
            'provider'  => $data['provider'],
            'amount'    => $ticket->price,
            'currency'  => $ticket->currency,
            'status'    => 'pending',
        ]);

        return response()->json([
            'message' => 'Payment created',
            'payment' => $payment,
        ], 201);
    }

    
    public function confirm($id)
    {
        $payment = Payment::findOrFail($id);

        $payment->update([
            'status'  => 'paid',
            'paid_at' => now(),
        ]);

        
        $payment->ticket->update([
            'status' => 'paid',
        ]);

        return response()->json([
            'message' => 'Payment confirmed',
            'payment' => $payment,
        ]);
    }

    
    public function refund($id)
    {
        $payment = Payment::findOrFail($id);

        $payment->update([
            'status' => 'refunded',
        ]);

        return response()->json([
            'message' => 'Payment refunded',
            'payment' => $payment,
        ]);
    }
}
