<?php

namespace App\Http\Controllers;

use App\Models\StopTime;
use App\Models\TrainPosition;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MapController extends Controller
{
    /**
     * Latest known position of every train (initial map state; live updates come over the
     * "map-trains" WebSocket channel as "TrainPositionUpdated" events).
     */
    public function trains(): JsonResponse
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
