<?php

namespace App\Http\Controllers;

use App\Models\Stop;
use App\Models\StopTime;
use App\Models\Trip;
use App\Services\TimetablePositionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * The map shows positions ESTIMATED from the timetable (TimetablePositionService),
 * moved by the delays and cancellations in trip_statuses. There is no live GPS feed.
 */
class MapController extends Controller
{
    public function trains(TimetablePositionService $positions): JsonResponse
    {
        return response()->json($positions->current());
    }

    /**
     * Every station with coordinates, for the station layer on the map.
     */
    public function stations(): JsonResponse
    {
        return response()->json(Cache::remember('map.stations', 3600, fn () => Stop::orderBy('stop_name')
            ->get(['stop_id', 'stop_name', 'stop_lat', 'stop_lon'])
            ->map(fn (Stop $stop) => [
                'stop_id' => $stop->stop_id,
                'stop_name' => $stop->stop_name,
                'stop_lat' => (float) $stop->stop_lat,
                'stop_lon' => (float) $stop->stop_lon,
            ])
            ->all()));
    }

    /**
     * A trip's stops in order, with names and times, for drawing its route on the map.
     */
    public function route(string $tripId): JsonResponse
    {
        $trip = Trip::with('route')->findOrFail($tripId);

        $stops = StopTime::where('trip_id', $tripId)
            ->orderBy('stop_sequence')
            ->with('stop')
            ->get()
            ->filter(fn (StopTime $stopTime) => $stopTime->stop
                && $stopTime->stop->stop_lon !== null
                && $stopTime->stop->stop_lat !== null)
            ->map(fn (StopTime $stopTime) => [
                'stop_id' => $stopTime->stop_id,
                'stop_name' => $stopTime->stop->stop_name,
                'stop_sequence' => $stopTime->stop_sequence,
                // Mapbox needs numbers, not decimal strings
                'longitude' => (float) $stopTime->stop->stop_lon,
                'latitude' => (float) $stopTime->stop->stop_lat,
                'arrival_time' => $stopTime->arrival_time,
                'departure_time' => $stopTime->departure_time,
            ])
            ->values();

        return response()->json([
            'trip_id' => $trip->trip_id,
            'headsign' => $trip->trip_headsign,
            'route_name' => $trip->route?->route_short_name ?: $trip->route?->route_long_name,
            'stops' => $stops,
        ]);
    }
}
