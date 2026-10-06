<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Trip;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

class RouteController extends Controller
{
    public function index(): LengthAwarePaginator
    {
        return Route::with('agency')->paginate(50);
    }

    public function show(string $route_id): Route
    {
        return Route::with(['agency', 'trips'])->findOrFail($route_id);
    }

    public function trips(string $route_id): JsonResponse
    {
        $trips = Trip::with(['stopTimes.stop', 'calendar'])
            ->where('route_id', $route_id)
            ->get();

        if ($trips->isEmpty()) {
            return response()->json(['error' => 'No trips found for this route'], 404);
        }

        return response()->json($trips);
    }
}
