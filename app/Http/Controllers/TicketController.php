<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class TicketController extends Controller
{
    public function __construct(private TicketService $tickets) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $request->user()->tickets()
            ->with(['journey.fromStop', 'journey.toStop', 'journey.trip.route', 'latestPayment'])
            ->latest()
            ->paginate(20);
    }

    public function show(Ticket $ticket): Ticket
    {
        Gate::authorize('manage', $ticket);

        return $ticket->load(['journey.fromStop', 'journey.toStop', 'journey.trip.route', 'payments']);
    }

    /**
     * Buy a ticket for one /search-trains result. Send its trip_id, from_stop_id, to_stop_id and the travel date.
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
        );

        return response()->json([
            'message' => 'Ticket created, waiting for payment',
            'ticket' => $ticket->load(['journey.fromStop', 'journey.toStop']),
        ], 201);
    }

    public function cancel(Ticket $ticket): JsonResponse
    {
        Gate::authorize('manage', $ticket);

        return response()->json([
            'message' => 'Ticket cancelled',
            'ticket' => $this->tickets->cancel($ticket),
        ]);
    }
}
