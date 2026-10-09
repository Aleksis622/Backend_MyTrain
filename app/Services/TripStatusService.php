<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TripStatus;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Delays and cancellations of trips (used by the admin panel, the profile and the trains:* commands).
 * Only the status changes here; the timetable itself always stays as the seeders loaded it.
 */
class TripStatusService
{
    public function __construct(private TrainSearchService $trains) {}

    /**
     * Set a trip's status for one day. "on_time" removes the row.
     */
    public function set(string $tripId, CarbonInterface $date, string $status, int $minutes = 0, ?string $reason = null): ?TripStatus
    {
        $tripStatus = null;

        if ($status === TripStatus::ON_TIME) {
            TripStatus::where('trip_id', $tripId)->whereDate('service_date', $date)->delete();
        } else {
            $tripStatus = TripStatus::updateOrCreate(
                ['trip_id' => $tripId, 'service_date' => $date->toDateString()],
                [
                    'status' => $status,
                    'delay_minutes' => $status === TripStatus::DELAYED ? $minutes : 0,
                    'reason' => $reason,
                ],
            );
        }

        // The map caches train positions for a few seconds.
        Cache::forget('map.timetable-positions');

        return $tripStatus;
    }

    /**
     * Adds "train_status" { status, delay_minutes, reason } to each ticket: is its train
     * delayed or cancelled on the travel day? One query for the whole list.
     *
     * @param  iterable<Ticket>  $tickets  with journey loaded
     */
    public function attachToTickets(iterable $tickets): void
    {
        $tickets = collect($tickets)->filter(fn (Ticket $ticket) => $ticket->journey !== null);
        if ($tickets->isEmpty()) {
            return;
        }

        $key = fn (string $tripId, string $date) => "{$tripId}|{$date}";
        $travelDay = fn (Ticket $ticket) => $ticket->journey->departure_time->toDateString();

        $statuses = TripStatus::whereIn('trip_id', $tickets->map(fn (Ticket $ticket) => $ticket->journey->trip_id)->unique())
            ->whereIn('service_date', $tickets->map($travelDay)->unique())
            ->get()
            ->keyBy(fn (TripStatus $status) => $key($status->trip_id, substr($status->service_date, 0, 10)));

        foreach ($tickets as $ticket) {
            $status = $statuses->get($key($ticket->journey->trip_id, $travelDay($ticket)));

            $ticket->setAttribute('train_status', [
                'status' => $status->status ?? TripStatus::ON_TIME,
                'delay_minutes' => $status->delay_minutes ?? 0,
                'reason' => $status?->reason,
            ]);
        }
    }

    /**
     * Every trip running on $date with its origin, destination, times and status.
     *
     * @return Collection<int, object>
     */
    public function tripsOn(CarbonInterface $date): Collection
    {
        $ends = DB::table('stop_times')
            ->select('trip_id', DB::raw('MIN(stop_sequence) AS first_sequence'), DB::raw('MAX(stop_sequence) AS last_sequence'))
            ->groupBy('trip_id');

        return DB::table('trips')
            ->joinSub($ends, 'ends', 'ends.trip_id', '=', 'trips.trip_id')
            ->join('stop_times as first', function ($join) {
                $join->on('first.trip_id', '=', 'trips.trip_id')->on('first.stop_sequence', '=', 'ends.first_sequence');
            })
            ->join('stop_times as last', function ($join) {
                $join->on('last.trip_id', '=', 'trips.trip_id')->on('last.stop_sequence', '=', 'ends.last_sequence');
            })
            ->join('stops as origin', 'origin.stop_id', '=', 'first.stop_id')
            ->join('stops as destination', 'destination.stop_id', '=', 'last.stop_id')
            ->join('routes', 'routes.route_id', '=', 'trips.route_id')
            ->leftJoin('trip_statuses', fn ($join) => $this->trains->joinStatus($join, $date))
            ->whereIn('trips.service_id', $this->trains->activeServiceIds($date))
            ->select(
                'trips.trip_id',
                'trips.trip_headsign',
                'routes.route_short_name',
                'routes.route_long_name',
                'origin.stop_name as origin',
                'first.departure_time',
                'destination.stop_name as destination',
                'last.arrival_time',
                ...$this->trains->statusColumns(),
            )
            ->orderBy('first.departure_time')
            ->get();
    }
}
