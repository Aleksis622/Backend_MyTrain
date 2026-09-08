<?php

namespace App\Http\Controllers;

use App\Models\Stop;

class StopController extends Controller
{
    public function index()
    {
        return Stop::all();
    }

    public function show($stop_id)
    {
        $stop = Stop::where('stop_id', $stop_id)->first();

        if (!$stop) {
            return response()->json(['error' => 'Stop not found'], 404);
        }

        return $stop;
    }

    public function stopTimes($stop_id)
    {
        $times = \App\Models\StopTime::where('stop_id', $stop_id)->get();

        if ($times->isEmpty()) {
            return response()->json(['error' => 'No stop times found for this stop'], 404);
        }

        return $times;
    }
}
