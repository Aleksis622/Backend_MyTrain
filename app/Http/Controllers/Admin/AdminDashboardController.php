<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TripStatus;
use App\Models\User;
use App\Services\TimetablePositionService;
use App\Services\TripStatusService;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    /**
     * Numbers for the admin start page.
     */
    public function __invoke(TimetablePositionService $positions, TripStatusService $statuses): JsonResponse
    {
        $todayStatuses = TripStatus::whereDate('service_date', today())->get();

        return response()->json([
            'users' => User::where('is_admin', false)->count(),
            'tickets_today' => Ticket::whereDate('purchased_at', today())->count(),
            'tickets_by_status' => Ticket::selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status'),
            'tickets_last_7_days' => $this->ticketsPerDay(7),
            'revenue_today' => (float) Payment::where('status', 'paid')->whereDate('paid_at', today())->sum('amount'),
            'revenue_total' => (float) Payment::where('status', 'paid')->sum('amount'),
            'trains_running' => count($positions->current()),
            'delayed_today' => $todayStatuses->where('status', TripStatus::DELAYED)->count(),
            'cancelled_today' => $todayStatuses->where('status', TripStatus::CANCELLED)->count(),
            // Today's delayed and cancelled trains, in departure order.
            'disruptions_today' => $statuses->tripsOn(today())
                ->where('status', '!=', TripStatus::ON_TIME)
                ->take(5)
                ->values(),
            'latest_tickets' => Ticket::with(['user:id,name,email', 'journey.fromStop', 'journey.toStop'])
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    /**
     * Tickets bought on each of the last $days days (oldest first, days without sales included):
     * [['date' => '2026-10-03', 'total' => 4], ...]
     */
    private function ticketsPerDay(int $days): array
    {
        $first = today()->subDays($days - 1);

        $totals = Ticket::whereDate('purchased_at', '>=', $first)
            ->selectRaw('DATE(purchased_at) AS day, COUNT(*) AS total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(0, $days - 1))
            ->map(function (int $offset) use ($first, $totals) {
                $date = $first->copy()->addDays($offset)->toDateString();

                return ['date' => $date, 'total' => (int) ($totals[$date] ?? 0)];
            })
            ->all();
    }
}
