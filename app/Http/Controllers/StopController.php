<?php

namespace App\Http\Controllers;

use App\Models\Stop;
use App\Services\TrainSearchService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

    /**
     * GET /stops/{stop_id}/departures?date=2026-10-07&after=08:00&limit=20
     * Without "date" it shows today's trains from now on; with a future date, from 00:00.
     */
    public function departures(Request $request, string $stop_id, TrainSearchService $trains): JsonResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'after' => 'nullable|date_format:H:i',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $stop = Stop::findOrFail($stop_id, ['stop_id', 'stop_name', 'stop_lat', 'stop_lon']);
        $date = Carbon::parse($data['date'] ?? today());
        $after = $data['after'] ?? ($date->isToday() ? now()->format('H:i') : null);

        return response()->json([
            'stop' => $stop,
            'date' => $date->toDateString(),
            'after' => $after,
            'departures' => $trains->departures($stop->stop_id, $date, $after, $data['limit'] ?? 20),
        ]);
    }
}
