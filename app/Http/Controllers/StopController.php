<?php

namespace App\Http\Controllers;

use App\Models\Stop;
use App\Models\StopTime;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StopController extends Controller
{
    /**
     * GET /stops                 -> paginated list of all stations
     * GET /stops?search=rīg      -> up to 10 matches for station autocomplete
     */
    public function index(Request $request): LengthAwarePaginator|Collection
    {
        $search = trim((string) $request->query('search', ''));

        if ($search === '') {
            return Stop::orderBy('stop_name')->paginate(100);
        }

        return Stop::whereRaw('LOWER(stop_name) LIKE ?', ['%'.mb_strtolower($search).'%'])
            // names starting with the search text first ("Rīga" before "Šķirotava (Rīga)")
            ->orderByRaw('LOWER(stop_name) LIKE ? DESC', [mb_strtolower($search).'%'])
            ->orderBy('stop_name')
            ->limit(10)
            ->get(['stop_id', 'stop_name', 'stop_lat', 'stop_lon']);
    }

    public function show(string $stop_id): Stop
    {
        return Stop::with(['originJourneys.train', 'destinationJourneys.train'])->findOrFail($stop_id);
    }

    public function stopTimes(string $stop_id): JsonResponse
    {
        $times = StopTime::with(['trip.route'])
            ->where('stop_id', $stop_id)
            ->orderBy('departure_time')
            ->get();

        if ($times->isEmpty()) {
            return response()->json(['error' => 'No stop times found for this stop'], 404);
        }

        return response()->json($times);
    }
}
