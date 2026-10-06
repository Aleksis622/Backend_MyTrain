<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Ticket;
use App\Services\PaymentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $request->user()->payments()
            ->with('ticket')
            ->latest()
            ->paginate(20);
    }

    public function show(Payment $payment): Payment
    {
        Gate::authorize('manage', $payment);

        return $payment->load('ticket');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ticket_id' => 'required|integer|exists:tickets,id',
            'provider' => 'required|string|max:50',
        ]);

        $ticket = Ticket::findOrFail($data['ticket_id']);
        Gate::authorize('manage', $ticket);

        return response()->json([
            'message' => 'Payment created',
            'payment' => $this->payments->createPendingPayment($ticket, $data['provider']),
        ], 201);
    }

    public function confirm(Request $request, Payment $payment): JsonResponse
    {
        Gate::authorize('manage', $payment);

        $data = $request->validate([
            'provider_payment_id' => 'nullable|string|max:255',
        ]);

        return response()->json([
            'message' => 'Payment confirmed',
            'payment' => $this->payments->confirmPayment($payment, $data['provider_payment_id'] ?? null),
        ]);
    }

    public function refund(Payment $payment): JsonResponse
    {
        Gate::authorize('manage', $payment);

        return response()->json([
            'message' => 'Payment refunded',
            'payment' => $this->payments->refund($payment),
        ]);
    }
}
