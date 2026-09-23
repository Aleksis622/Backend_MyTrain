<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    protected $service;

    public function __construct(PaymentService $service)
    {
        $this->service = $service;
    }

    /**
     * List all payments for authenticated user
     */
    public function index(Request $request)
    {
        return Payment::where('user_id', $request->user()->id)
            ->with(['ticket'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    /**
     * Show a single payment
     */
    public function show(Request $request, $id)
    {
        $payment = Payment::with(['ticket', 'user'])->findOrFail($id);

        // Prevent viewing other users' payments
        if ($payment->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $payment;
    }

    /**
     * Create a new pending payment
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'provider'  => 'required|string',
        ]);

        $ticket = Ticket::findOrFail($data['ticket_id']);

        // Prevent paying someone else's ticket
        if ($ticket->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $payment = $this->service->createPendingPayment(
                $ticket,
                $request->user()->id,
                $data['provider']
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }

        return response()->json([
            'message' => 'Payment created',
            'payment' => $payment,
        ], 201);
    }

    /**
     * Confirm payment (provider callback or manual)
     */
    public function confirm(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        // Prevent confirming other users' payments
        if ($payment->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $confirmed = $this->service->confirmPayment(
                $payment,
                $request->provider_payment_id ?? null
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }

        return response()->json([
            'message' => 'Payment confirmed',
            'payment' => $confirmed,
        ]);
    }

    /**
     * Refund payment
     */
    public function refund(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        // Prevent refunding other users' payments
        if ($payment->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $refunded = $this->service->refund($payment);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }

        return response()->json([
            'message' => 'Payment refunded',
            'payment' => $refunded,
        ]);
    }
    
}
