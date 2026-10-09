<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentProviderException;
use App\Models\Payment;
use App\Models\Ticket;
use App\Services\PaymentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Paying happens on Stripe's own checkout page (test mode). The app never marks a payment
 * as paid because the user asks: only Stripe's signed webhook or a server-side check with
 * Stripe can (see PaymentService and StripeWebhookController).
 */
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

    /**
     * The payment's real status. While it is unfinished the server asks Stripe itself,
     * so the result page is right even if the webhook has not arrived yet.
     */
    public function show(Payment $payment): Payment
    {
        Gate::authorize('manage', $payment);

        if ($payment->status === Payment::PENDING) {
            try {
                $payment = $this->payments->syncFromProvider($payment);
            } catch (PaymentProviderException) {
                // Stripe not reachable right now: show the stored status, the page asks again
            }
        }

        return $payment->load('ticket.journey.fromStop', 'ticket.journey.toStop');
    }

    /**
     * POST /payments { ticket_id } -> { payment, checkout_url }: the frontend sends the user to checkout_url.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ticket_id' => 'required|integer|exists:tickets,id',
        ]);

        $ticket = Ticket::findOrFail($data['ticket_id']);
        Gate::authorize('manage', $ticket);

        $payment = $this->payments->startCheckout($ticket, $request->user()->language ?? app()->getLocale());

        return response()->json([
            'message' => 'Checkout started',
            'payment' => $payment,
            'checkout_url' => $payment->checkout_url,
        ], 201);
    }

    /**
     * The user came back from Stripe with "cancel": close the checkout page and the payment.
     */
    public function cancel(Payment $payment): JsonResponse
    {
        Gate::authorize('manage', $payment);

        return response()->json([
            'message' => 'Payment cancelled',
            'payment' => $this->payments->cancelCheckout($payment),
        ]);
    }

    /**
     * Passengers can refund until the train leaves (or any time when the railway cancelled it).
     */
    public function refund(Payment $payment): JsonResponse
    {
        Gate::authorize('manage', $payment);

        return response()->json([
            'message' => 'Payment refunded',
            'payment' => $this->payments->refund($payment),
        ]);
    }
}
