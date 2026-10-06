<?php

namespace App\Http\Controllers;

use App\Models\StopTime;
use App\Models\TrainPosition;
use App\Services\TimetablePositionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MapController extends Controller
{
    /**
     * Where every running train should be right now, according to the timetable.
     * The map polls this every few seconds; the result is cached for 10 seconds
     * so many open maps don't recalculate it on every request.
     */
    public function trains(TimetablePositionService $positions): JsonResponse
    {
        return response()->json(
            Cache::remember('map.timetable-positions', 10, fn () => $positions->positionsAt(now())->all())
        );
    }

    /**
     * Latest real GPS position of every train (from POST /train-positions, e.g. the
     * trains:simulate command or a future GPS feed). Live updates also come over the
     * "map-trains" WebSocket channel as "TrainPositionUpdated" events.
     */
    public function gpsPositions(): JsonResponse
    {
        $latestPositions = TrainPosition::select('train_positions.*')
            ->joinSub(
                TrainPosition::select('train_id', DB::raw('MAX(reported_at) AS latest'))->groupBy('train_id'),
                'lp',
                function ($join) {
                    $join->on('train_positions.train_id', '=', 'lp.train_id')
                        ->on('train_positions.reported_at', '=', 'lp.latest');
                }
            )
            ->with('train')
            ->orderByDesc('reported_at')
            ->get();

        return response()->json($latestPositions);
    }

    public function history(int $trainId): JsonResponse
    {
        $history = TrainPosition::where('train_id', $trainId)
            ->orderByDesc('reported_at')
            ->limit(500)
            ->get();

        if ($history->isEmpty()) {
            return response()->json(['error' => 'No position history found'], 404);
        }

        return response()->json($history);
    }

    /**
     * [lon, lat] pairs of a trip's stops in order, ready for a Mapbox LineString.
     */
    public function route(string $tripId): JsonResponse
    {
        $coords = StopTime::where('trip_id', $tripId)
            ->orderBy('stop_sequence')
            ->with('stop')
            ->get()
            ->filter(fn (StopTime $stopTime) => $stopTime->stop
                && $stopTime->stop->stop_lon !== null
                && $stopTime->stop->stop_lat !== null)
            ->map(fn (StopTime $stopTime) => [
                (float) $stopTime->stop->stop_lon,
                (float) $stopTime->stop->stop_lat,
            ])
            ->values();

        return response()->json($coords);
    }
}
