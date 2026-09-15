<?php

namespace App\Http\Controllers;

use App\Models\Trip;

class TripController extends Controller
{
    public function index()
    {
        return Trip::with(['route', 'calendar', 'calendarDates'])
            ->paginate(50);
    }

    public function show($trip_id)
    {
        $trip = Trip::with(['route', 'stopTimes.stop', 'calendar', 'calendarDates'])
            ->where('trip_id', $trip_id)
            ->first();

        if (!$trip) {
            return response()->json(['error' => 'Trip not found'], 404);
        }

        return $trip;
    }

    public function stopTimes($trip_id)
    {
        $times = \App\Models\StopTime::with('stop')
            ->where('trip_id', $trip_id)
            ->orderBy('stop_sequence')
            ->get();

        if ($times->isEmpty()) {
            return response()->json(['error' => 'No stop times found for this trip'], 404);
        }

        return $times;
    }
}
