<?php

namespace App\Http\Controllers;

use App\Models\TrainPosition;
use Illuminate\Support\Facades\DB;

class MapController extends Controller
{
    
    public function trains()
    {
        
        $latestPositions = TrainPosition::select(
                'train_positions.*'
            )
            ->join(
                DB::raw('(SELECT train_id, MAX(reported_at) AS latest FROM train_positions GROUP BY train_id) AS lp'),
                function ($join) {
                    $join->on('train_positions.train_id', '=', 'lp.train_id')
                         ->on('train_positions.reported_at', '=', 'lp.latest');
                }
            )
            ->with(['train'])
            ->orderBy('reported_at', 'desc')
            ->get();

        return $latestPositions;
    }

    public function history($trainId)
    {
        $history = TrainPosition::with('train')
            ->where('train_id', $trainId)
            ->orderBy('reported_at', 'desc')
            ->get();

        if ($history->isEmpty()) {
            return response()->json(['error' => 'No position history found'], 404);
        }

        return $history;
    }
    public function route($tripId)
{
    $stops = \App\Models\StopTime::where('trip_id', $tripId)
        ->orderBy('stop_sequence')
        ->with('stop')
        ->get();

    $coords = $stops->map(fn($s) => [
        $s->stop->stop_lon,
        $s->stop->stop_lat
    ]);

    return response()->json($coords);
}

}
