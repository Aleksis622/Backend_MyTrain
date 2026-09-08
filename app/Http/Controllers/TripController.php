<?php

namespace App\Http\Controllers;

use App\Models\Trip;

class TripController extends Controller
{
    public function index()
    {
        return Trip::all();
    }

    public function show($trip_id)
    {
        $trip = Trip::where('trip_id', $trip_id)->first();

        if (!$trip) {
            return response()->json(['error' => 'Trip not found'], 404);
        }

        return $trip;
    }

    public function stopTimes($trip_id)
    {
        $times = \App\Models\StopTime::where('trip_id', $trip_id)->get();

        if ($times->isEmpty()) {
            return response()->json(['error' => 'No stop times found for this trip'], 404);
        }

        return $times;
    }
}
