<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use App\Models\Ticket;
use App\Services\TripStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * GET /profile/overview: numbers for the profile header, the ticket tab counts
     * and the next trip (the soonest paid ticket whose train has not left yet).
     */
    public function overview(Request $request, TripStatusService $statuses): JsonResponse
    {
        $user = $request->user();
        $closed = [Ticket::CANCELLED, Ticket::REFUNDED];
        $notClosed = fn () => $user->tickets()->whereNotIn('status', $closed);
        $leaves = fn (string $operator) => fn ($journey) => $journey->where('departure_time', $operator, now());

        $nextTrip = $user->tickets()
            ->where('status', Ticket::PAID)
            ->whereHas('journey', $leaves('>='))
            ->with(['journey.fromStop', 'journey.toStop', 'journey.trip.route'])
            ->orderBy(Journey::select('departure_time')->whereColumn('journeys.id', 'tickets.journey_id'))
            ->first();

        if ($nextTrip) {
            $statuses->attachToTickets([$nextTrip]);
        }

        return response()->json([
            'stats' => $user->tripStats(),
            'counts' => [
                'upcoming' => $notClosed()->whereHas('journey', $leaves('>='))->count(),
                'past' => $notClosed()->whereHas('journey', $leaves('<'))->count(),
                'cancelled' => $user->tickets()->whereIn('status', $closed)->count(),
                // upcoming tickets still waiting for payment
                'unpaid' => $user->tickets()->where('status', Ticket::PENDING)->whereHas('journey', $leaves('>='))->count(),
            ],
            'next_trip' => $nextTrip,
        ]);
    }
}
