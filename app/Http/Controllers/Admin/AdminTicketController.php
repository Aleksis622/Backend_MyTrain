<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Ticket;
use App\Services\PaymentService;
use App\Services\TicketService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class AdminTicketController extends Controller
{
    private const RELATIONS = ['user:id,name,email', 'journey.fromStop', 'journey.toStop', 'journey.trip.route'];


    public function index(Request $request): LengthAwarePaginator
    {
        $data = $request->validate([
            'status' => 'nullable|in:pending,paid,cancelled,refunded',
            'search' => 'nullable|string|max:100',
            'date' => 'nullable|date_format:Y-m-d',
        ]);
        $search = trim($data['search'] ?? '');

        return Ticket::with([...self::RELATIONS, 'latestPayment'])
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($data['date'] ?? null, fn ($query, $date) => $query->whereHas(
                'journey', fn ($journey) => $journey->whereDate('departure_time', $date)
            ))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('ticket_code', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(20);
    }

    public function show(Ticket $ticket): Ticket
    {
        return $this->withDetails($ticket);
    }

    public function cancel(Request $request, Ticket $ticket, TicketService $tickets): JsonResponse
    {
        $tickets->cancel($ticket);
        $this->log($request, AdminAction::TICKET_CANCELLED, $ticket);

        return response()->json(['message' => 'Ticket cancelled', 'ticket' => $this->withDetails($ticket)]);
    }

    public function markPaid(Request $request, Ticket $ticket, PaymentService $payments): JsonResponse
    {
        // Paid at the ticket office: recorded as a manual payment (any open Stripe checkout is closed).
        $payments->recordManualPayment($ticket, $request->user()->id);
        $this->log($request, AdminAction::TICKET_MARKED_PAID, $ticket);

        return response()->json(['message' => 'Ticket marked as paid', 'ticket' => $this->withDetails($ticket)]);
    }

    public function refund(Request $request, Ticket $ticket, PaymentService $payments): JsonResponse
    {
        $payment = $ticket->payments()->where('status', 'paid')->latest()->first();

        if (! $payment) {
            throw new BookingException('This ticket has no paid payment to refund.');
        }

        $payments->refund($payment, byAdmin: true); // admins may refund after departure too
        $this->log($request, AdminAction::TICKET_REFUNDED, $ticket, ['amount' => (float) $payment->amount]);

        return response()->json(['message' => 'Ticket refunded', 'ticket' => $this->withDetails($ticket)]);
    }

    private function log(Request $request, string $action, Ticket $ticket, array $details = []): void
    {
        AdminAction::record($request->user(), $action, 'ticket', $ticket->id, [
            'ticket_code' => $ticket->ticket_code,
            'passenger' => $ticket->user?->name,
            ...$details,
        ]);
    }

    private function withDetails(Ticket $ticket): Ticket
    {
        return $ticket->refresh()->load([...self::RELATIONS, 'payments']);
    }
}
