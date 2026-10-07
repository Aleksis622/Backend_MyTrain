<?php

namespace App\Services;

use App\Models\Stop;
use App\Models\StopTime;
use App\Models\TripStatus;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Where every running train should be right now according to the GTFS timetable.
 *
 * There is no real GPS feed, so a train between two stations is placed on the straight
 * line between them, as far along as the elapsed time says. Delays are not known.
 */
class TimetablePositionService
{
    public function __construct(private TrainSearchService $trains) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function positionsAt(CarbonInterface $now): Collection
    {
        $secondsToday = (int) $now->secondsSinceMidnight();

        // GTFS times can pass 24:00, so a trip of yesterday's timetable may still be running
        // (00:40 today is "24:40:00" on yesterday's service day).
        return $this->positionsOnServiceDay($now->copy()->startOfDay(), $secondsToday)
            ->merge($this->positionsOnServiceDay($now->copy()->subDay()->startOfDay(), $secondsToday + 86400))
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function positionsOnServiceDay(CarbonInterface $serviceDay, int $seconds): Collection
    {
        $time = $this->toGtfsTime($seconds);

        // Delays and cancellations of that day. A late train can still be running after its timetable ended.
        $statuses = TripStatus::forDate($serviceDay);
        $cancelled = $statuses->where('status', TripStatus::CANCELLED)->keys();
        $longestDelay = (int) $statuses->where('status', TripStatus::DELAYED)->max('delay_minutes');
        $earliestEnd = $this->toGtfsTime(max(0, $seconds - $longestDelay * 60));

        $runningTripIds = DB::table('stop_times')
            ->join('trips', 'trips.trip_id', '=', 'stop_times.trip_id')
            ->whereIn('trips.service_id', $this->trains->activeServiceIds($serviceDay))
            ->whereNotIn('trips.trip_id', $cancelled)
            ->groupBy('stop_times.trip_id')
            ->havingRaw('MIN(stop_times.departure_time) <= ? AND MAX(stop_times.arrival_time) >= ?', [$time, $earliestEnd])
            ->pluck('stop_times.trip_id');

        if ($runningTripIds->isEmpty()) {
            return collect();
        }

        return StopTime::with(['stop', 'trip.route'])
            ->whereIn('trip_id', $runningTripIds)
            ->orderBy('trip_id')
            ->orderBy('stop_sequence')
            ->get()
            ->groupBy('trip_id')
            ->map(function (Collection $stopTimes, string $tripId) use ($statuses, $seconds) {
                $status = $statuses->get($tripId);
                $delay = $status?->status === TripStatus::DELAYED ? $status->delay_minutes : 0;

                // A train 5 minutes late is where the timetable had it 5 minutes ago.
                $position = $this->positionOnTrip($stopTimes->values(), $seconds - $delay * 60);

                return $position === null ? null : [
                    ...$position,
                    'service_status' => $delay > 0 ? TripStatus::DELAYED : TripStatus::ON_TIME,
                    'delay_minutes' => $delay,
                    'status_reason' => $status?->reason,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<int, StopTime>  $stopTimes  one trip, in stop order
     * @return array<string, mixed>|null
     */
    private function positionOnTrip(Collection $stopTimes, int $seconds): ?array
    {
        foreach ($stopTimes as $index => $current) {
            $next = $stopTimes->get($index + 1);

            // Standing at a station.
            if ($seconds >= $this->toSeconds($current->arrival_time) && $seconds <= $this->toSeconds($current->departure_time)) {
                return $this->describe($stopTimes, $index, 0.0, 'at_station');
            }

            // Between this station and the next one.
            if ($next && $seconds > $this->toSeconds($current->departure_time) && $seconds < $this->toSeconds($next->arrival_time)) {
                $departed = $this->toSeconds($current->departure_time);
                $progress = ($seconds - $departed) / max(1, $this->toSeconds($next->arrival_time) - $departed);

                return $this->describe($stopTimes, $index, $progress, 'moving');
            }
        }

        return null;
    }

    /**
     * Everything the map shows about one train. $index is the stop the train is at,
     * or the stop it left last; $progress is how far it is towards the next stop (0-1).
     *
     * @param  Collection<int, StopTime>  $stopTimes  one trip, in stop order
     * @return array<string, mixed>|null
     */
    private function describe(Collection $stopTimes, int $index, float $progress, string $status): ?array
    {
        $from = $stopTimes->get($index);
        $next = $stopTimes->get($index + 1);
        $previous = $stopTimes->get($index - 1);
        $origin = $stopTimes->first();
        $destination = $stopTimes->last();

        $first = $from->stop;
        $second = ($next ?? $from)->stop;

        if (! $first || ! $second) {
            return null;
        }

        $lat = (float) $first->stop_lat + ((float) $second->stop_lat - (float) $first->stop_lat) * $progress;
        $lon = (float) $first->stop_lon + ((float) $second->stop_lon - (float) $first->stop_lon) * $progress;

        // Direction of travel: towards the next stop, or (at the last stop) the way it came in.
        $heading = match (true) {
            $next?->stop !== null => $this->bearing($first, $next->stop),
            $previous?->stop !== null => $this->bearing($previous->stop, $first),
            default => null,
        };

        return [
            'trip_id' => $from->trip_id,
            'route_name' => $from->trip?->route?->route_short_name ?: $from->trip?->route?->route_long_name,
            'headsign' => $from->trip?->trip_headsign,
            'status' => $status,
            'latitude' => round($lat, 6),
            'longitude' => round($lon, 6),
            'heading' => $heading === null ? null : round($heading),
            'current_stop' => $status === 'at_station' ? $first->stop_name : null,
            'current_stop_departure' => $status === 'at_station' ? $from->departure_time : null,
            'previous_stop' => $status === 'moving' ? $first->stop_name : null,
            'next_stop' => $next?->stop?->stop_name,
            'next_stop_id' => $next?->stop_id,
            'next_arrival' => $next?->arrival_time, // GTFS time, may be past 24:00
            'origin' => $origin->stop?->stop_name,
            'destination' => $destination->stop?->stop_name,
            'destination_stop_id' => $destination->stop_id,
            'destination_arrival' => $destination->arrival_time,
            'stop_number' => $index + 1,
            'stops_total' => $stopTimes->count(),
            // stop the train is at / left last; splits the route line into "passed" and "ahead"
            'last_stop_sequence' => $from->stop_sequence,
            'source' => 'timetable',
        ];
    }

    /**
     * Compass direction (0-360 degrees, 0 = north) from one stop to another.
     */
    private function bearing(Stop $from, Stop $to): float
    {
        [$lat1, $lon1, $lat2, $lon2] = array_map('deg2rad', [
            (float) $from->stop_lat, (float) $from->stop_lon, (float) $to->stop_lat, (float) $to->stop_lon,
        ]);

        $y = sin($lon2 - $lon1) * cos($lat2);
        $x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($lon2 - $lon1);

        return fmod(rad2deg(atan2($y, $x)) + 360, 360);
    }

    private function toSeconds(string $gtfsTime): int
    {
        [$hours, $minutes, $seconds] = array_map('intval', explode(':', $gtfsTime));

        return $hours * 3600 + $minutes * 60 + $seconds;
    }

    private function toGtfsTime(int $seconds): string
    {
        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }
}
