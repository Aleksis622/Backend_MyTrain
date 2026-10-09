<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingException;
use App\Models\Journey;
use App\Models\Payment;
use App\Models\Ticket;
use App\Services\PaymentService;
use App\Services\TicketService;
use App\Services\TripStatusService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class TicketController extends Controller
{
    public function __construct(
        private TicketService $tickets,
        private TripStatusService $statuses,
        private PaymentService $payments,
    ) {}

    /**
     * GET /tickets?scope=upcoming|past|cancelled&page=2 (no scope = all, newest first).
     * Each ticket has "train_status" (is its train delayed or cancelled that day?)
     * and "refundable" (may the passenger refund it now?).
     */
    public function index(Request $request): LengthAwarePaginator
    {
        $data = $request->validate(['scope' => 'nullable|in:upcoming,past,cancelled']);
        $departure = Journey::select('departure_time')->whereColumn('journeys.id', 'tickets.journey_id');
        $closed = [Ticket::CANCELLED, Ticket::REFUNDED];

        $page = $request->user()->tickets()
            ->with(['journey.fromStop', 'journey.toStop', 'journey.trip.route', 'latestPayment'])
            ->when($data['scope'] ?? null, fn ($query, $scope) => match ($scope) {
                // soonest first
                'upcoming' => $query->whereNotIn('status', $closed)
                    ->whereHas('journey', fn ($journey) => $journey->where('departure_time', '>=', now()))
                    ->orderBy($departure),
                // most recent trip first
                'past' => $query->whereNotIn('status', $closed)
                    ->whereHas('journey', fn ($journey) => $journey->where('departure_time', '<', now()))
                    ->orderByDesc($departure),
                'cancelled' => $query->whereIn('status', $closed)->latest('updated_at'),
            }, fn ($query) => $query->latest())
            ->paginate(10);

        $this->statuses->attachToTickets($page->items());
        foreach ($page->items() as $ticket) {
            $ticket->setAttribute('refundable', $this->payments->userMayRefund($ticket));
        }

        return $page;
    }

    public function show(Ticket $ticket): Ticket
    {
        Gate::authorize('manage', $ticket);

        return $ticket->load(['journey.fromStop', 'journey.toStop', 'journey.trip.route', 'payments']);
    }

    /**
     * Buy a ticket for one /search-trains result. Send its trip_id, from_stop_id, to_stop_id and the travel date.
     * Buying the same train again while that ticket is unpaid returns it (200) instead of a copy.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'trip_id' => 'required|string|exists:trips,trip_id',
            'from_stop_id' => 'required|string|exists:stops,stop_id',
            'to_stop_id' => 'required|string|exists:stops,stop_id|different:from_stop_id',
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
        ]);

        $ticket = $this->tickets->purchase(
            $request->user(),
            $data['trip_id'],
            $data['from_stop_id'],
            $data['to_stop_id'],
            Carbon::parse($data['date']),
            $reused,
        );

        return response()->json([
            'message' => $reused ? 'You already have an unpaid ticket for this train' : 'Ticket created, waiting for payment',
            'ticket' => $ticket->load(['journey.fromStop', 'journey.toStop']),
        ], $reused ? 200 : 201);
    }

    public function cancel(Ticket $ticket): JsonResponse
    {
        Gate::authorize('manage', $ticket);

        return response()->json([
            'message' => 'Ticket cancelled',
            'ticket' => $this->tickets->cancel($ticket),
        ]);
    }

    /**
     * Refund a paid ticket (the server picks its paid payment). Allowed until the train leaves,
     * or any time when the railway cancelled it.
     */
    public function refund(Ticket $ticket): JsonResponse
    {
        Gate::authorize('manage', $ticket);

        $payment = $ticket->payments()->where('status', Payment::PAID)->first();
        if (! $payment) {
            throw new BookingException('Only paid tickets can be refunded.');
        }

        $this->payments->refund($payment);

        return response()->json([
            'message' => 'Ticket refunded',
            'ticket' => $ticket->refresh(),
        ]);
    }
}
