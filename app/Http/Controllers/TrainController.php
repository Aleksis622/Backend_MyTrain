<?php

namespace App\Http\Controllers;

use App\Services\TrainSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TrainController extends Controller
{
    /**
     * Static list for the home page until real statistics exist.
     *
     * @return list<array{id: int, name: string, description: string}>
     */
    public function popular(): array
    {
        return [
            ['id' => 1, 'name' => 'Rīga → Jelgava', 'description' => 'Fast trains every 30 minutes'],
            ['id' => 2, 'name' => 'Rīga → Sigulda', 'description' => 'Popular scenic route'],
            ['id' => 3, 'name' => 'Rīga → Daugavpils', 'description' => 'Long-distance express'],
        ];
    }

    /**
     * GET /search-trains?from=Rīga&to=Jelgava&date=2026-10-05
     * "from" / "to" accept a stop_id (from /stops?search=) or part of a station name.
     */
    public function search(Request $request, TrainSearchService $trains): JsonResponse
    {
        $data = $request->validate([
            'from' => 'required|string|max:100',
            'to' => 'required|string|max:100',
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $date = Carbon::parse($data['date'] ?? today());

        return response()->json($trains->search(
            $trains->resolveStopIds($data['from']),
            $trains->resolveStopIds($data['to']),
            $date,
        ));
    }
}
