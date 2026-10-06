<?php

namespace App\Http\Controllers;

use App\Models\StopTime;
use App\Models\Trip;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

class TripController extends Controller
{
    public function index(): LengthAwarePaginator
    {
        return Trip::with(['route', 'calendar', 'calendarDates'])->paginate(50);
    }

    public function show(string $trip_id): Trip
    {
        return Trip::with(['route', 'stopTimes.stop', 'calendar', 'calendarDates'])->findOrFail($trip_id);
    }

    public function stopTimes(string $trip_id): JsonResponse
    {
        $times = StopTime::with('stop')
            ->where('trip_id', $trip_id)
            ->orderBy('stop_sequence')
            ->get();

        if ($times->isEmpty()) {
            return response()->json(['error' => 'No stop times found for this trip'], 404);
        }

        return response()->json($times);
    }
}
