<?php

namespace App\Services;

use App\Models\Calendar;
use App\Models\CalendarDate;
use App\Models\Stop;
use App\Models\TripStatus;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrainSearchService
{
    /**
     * Resolve a station given either its stop_id or (part of) its name.
     *
     * @return Collection<int, string> matching stop_ids
     */
    public function resolveStopIds(string $stopIdOrName): Collection
    {
        if (Stop::whereKey($stopIdOrName)->exists()) {
            return collect([$stopIdOrName]);
        }

        return Stop::whereRaw('LOWER(stop_name) LIKE ?', ['%'.mb_strtolower($stopIdOrName).'%'])
            ->pluck('stop_id');
    }

    /**
     * Trips that stop at one of $fromStopIds and later at one of $toStopIds and run on $date.
     * $departAfter ("HH:MM") skips trains that leave earlier.
     *
     * @param  Collection<int, string>  $fromStopIds
     * @param  Collection<int, string>  $toStopIds
     * @return Collection<int, object{trip_id: string, trip_headsign: ?string, route_long_name: ?string, from_stop_id: string, from_station: string, to_stop_id: string, to_station: string, departure_time: string, arrival_time: string, price: ?string, currency: ?string, status: string, delay_minutes: int, status_reason: ?string}>
     */
    public function search(Collection $fromStopIds, Collection $toStopIds, CarbonInterface $date, ?string $tripId = null, int $limit = 20, ?string $departAfter = null): Collection
    {
        return DB::table('stop_times as st_from')
            ->join('stop_times as st_to', 'st_to.trip_id', '=', 'st_from.trip_id')
            ->join('stops as s_from', 's_from.stop_id', '=', 'st_from.stop_id')
            ->join('stops as s_to', 's_to.stop_id', '=', 'st_to.stop_id')
            ->join('trips', 'trips.trip_id', '=', 'st_from.trip_id')
            ->join('routes', 'routes.route_id', '=', 'trips.route_id')
            ->leftJoin('trip_statuses', fn ($join) => $this->joinStatus($join, $date))
            ->leftJoin('fare_rules', function ($join) {
                $join->on('fare_rules.origin_id', '=', 'st_from.stop_id')
                    ->on('fare_rules.destination_id', '=', 'st_to.stop_id');
            })
            ->leftJoin('fare_attributes', 'fare_attributes.fare_id', '=', 'fare_rules.fare_id')
            ->whereIn('st_from.stop_id', $fromStopIds)
            ->whereIn('st_to.stop_id', $toStopIds)
            // the train must reach "from" before "to"
            ->whereColumn('st_from.stop_sequence', '<', 'st_to.stop_sequence')
            ->whereIn('trips.service_id', $this->activeServiceIds($date))
            ->when($tripId, fn ($query) => $query->where('trips.trip_id', $tripId))
            // GTFS times are zero-padded "HH:MM:SS" strings, so text comparison works
            ->when($departAfter, fn ($query) => $query->where('st_from.departure_time', '>=', $departAfter.':00'))
            ->select(
                'trips.trip_id',
                'trips.trip_headsign',
                'routes.route_long_name',
                'st_from.stop_id as from_stop_id',
                's_from.stop_name as from_station',
                'st_to.stop_id as to_stop_id',
                's_to.stop_name as to_station',
                'st_from.departure_time',
                'st_to.arrival_time',
                'fare_attributes.price',
                'fare_attributes.currency_type as currency',
                ...$this->statusColumns(),
            )
            ->orderBy('st_from.departure_time')
            ->limit($limit)
            ->get();
    }

    /**
     * Departure board: trains leaving $stopId on $date (at or after $departAfter "HH:MM"),
     * with the station each train finally goes to. Trains that end at $stopId are left out.
     *
     * @return Collection<int, object{trip_id: string, trip_headsign: ?string, route_short_name: ?string, route_long_name: ?string, departure_time: string, destination_stop_id: string, destination: string, destination_arrival_time: string, status: string, delay_minutes: int, status_reason: ?string}>
     */
    public function departures(string $stopId, CarbonInterface $date, ?string $departAfter = null, int $limit = 20): Collection
    {
        $lastStops = DB::table('stop_times')
            ->select('trip_id', DB::raw('MAX(stop_sequence) AS last_sequence'))
            ->groupBy('trip_id');

        return DB::table('stop_times as here')
            ->join('trips', 'trips.trip_id', '=', 'here.trip_id')
            ->join('routes', 'routes.route_id', '=', 'trips.route_id')
            ->leftJoin('trip_statuses', fn ($join) => $this->joinStatus($join, $date))
            ->joinSub($lastStops, 'last', 'last.trip_id', '=', 'here.trip_id')
            ->join('stop_times as final', function ($join) {
                $join->on('final.trip_id', '=', 'here.trip_id')
                    ->on('final.stop_sequence', '=', 'last.last_sequence');
            })
            ->join('stops as destination', 'destination.stop_id', '=', 'final.stop_id')
            ->where('here.stop_id', $stopId)
            ->whereColumn('here.stop_sequence', '<', 'last.last_sequence')
            ->whereIn('trips.service_id', $this->activeServiceIds($date))
            ->when($departAfter, fn ($query) => $query->where('here.departure_time', '>=', $departAfter.':00'))
            ->select(
                'trips.trip_id',
                'trips.trip_headsign',
                'routes.route_short_name',
                'routes.route_long_name',
                'here.departure_time',
                'final.stop_id as destination_stop_id',
                'destination.stop_name as destination',
                'final.arrival_time as destination_arrival_time',
                ...$this->statusColumns(),
            )
            ->orderBy('here.departure_time')
            ->limit($limit)
            ->get();
    }

    /**
     * The trip's delay/cancellation on that day (trip_statuses has no row when it runs on time).
     */
    public function joinStatus(JoinClause $join, CarbonInterface $date): void
    {
        $join->on('trip_statuses.trip_id', '=', 'trips.trip_id')
            ->whereDate('trip_statuses.service_date', $date);
    }

    /**
     * status ("on_time", "delayed" or "cancelled"), delay_minutes and status_reason.
     *
     * @return list<Expression|string>
     */
    public function statusColumns(): array
    {
        return [
            DB::raw("COALESCE(trip_statuses.status, '".TripStatus::ON_TIME."') as status"),
            DB::raw('COALESCE(trip_statuses.delay_minutes, 0) as delay_minutes'),
            'trip_statuses.reason as status_reason',
        ];
    }

    /**
     * GTFS service_ids running on $date: calendar weekday + date range,
     * plus calendar_dates additions (type 1), minus removals (type 2).
     *
     * @return Collection<int, string>
     */
    public function activeServiceIds(CarbonInterface $date): Collection
    {
        $weekday = strtolower($date->englishDayOfWeek);

        $regular = Calendar::where($weekday, true)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->pluck('service_id');

        $exceptions = CalendarDate::whereDate('date', $date)->get(['service_id', 'exception_type']);

        $added = $exceptions->where('exception_type', 1)->pluck('service_id');
        $removed = $exceptions->where('exception_type', 2)->pluck('service_id');

        return $regular->merge($added)->diff($removed)->unique()->values();
    }
}
