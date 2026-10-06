<?php

namespace App\Services;

use App\Models\StopTime;
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
        $secondsToday = $now->secondsSinceMidnight();

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

        $runningTripIds = DB::table('stop_times')
            ->join('trips', 'trips.trip_id', '=', 'stop_times.trip_id')
            ->whereIn('trips.service_id', $this->trains->activeServiceIds($serviceDay))
            ->groupBy('stop_times.trip_id')
            ->havingRaw('MIN(stop_times.departure_time) <= ? AND MAX(stop_times.arrival_time) >= ?', [$time, $time])
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
            ->map(fn (Collection $stopTimes) => $this->positionOnTrip($stopTimes->values(), $seconds))
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
                return $this->describe($current, $next, 0.0, 'at_station');
            }

            // Between this station and the next one.
            if ($next && $seconds > $this->toSeconds($current->departure_time) && $seconds < $this->toSeconds($next->arrival_time)) {
                $departed = $this->toSeconds($current->departure_time);
                $progress = ($seconds - $departed) / max(1, $this->toSeconds($next->arrival_time) - $departed);

                return $this->describe($current, $next, $progress, 'moving');
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function describe(StopTime $from, ?StopTime $next, float $progress, string $status): ?array
    {
        $first = $from->stop;
        $second = ($next ?? $from)->stop;

        if (! $first || ! $second) {
            return null;
        }

        $lat = (float) $first->stop_lat + ((float) $second->stop_lat - (float) $first->stop_lat) * $progress;
        $lon = (float) $first->stop_lon + ((float) $second->stop_lon - (float) $first->stop_lon) * $progress;

        return [
            'trip_id' => $from->trip_id,
            'route_name' => $from->trip?->route?->route_short_name ?: $from->trip?->route?->route_long_name,
            'headsign' => $from->trip?->trip_headsign,
            'status' => $status,
            'latitude' => round($lat, 6),
            'longitude' => round($lon, 6),
            'current_stop' => $status === 'at_station' ? $first->stop_name : null,
            'previous_stop' => $status === 'moving' ? $first->stop_name : null,
            'next_stop' => $next?->stop?->stop_name,
            'next_arrival' => $next?->arrival_time, // GTFS time, may be past 24:00
            'source' => 'timetable',
        ];
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
