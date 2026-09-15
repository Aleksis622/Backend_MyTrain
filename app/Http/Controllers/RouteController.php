<?php

namespace App\Http\Controllers;

use App\Models\Route;

class RouteController extends Controller
{
    public function index()
    {
        return Route::with('agency')
            ->paginate(50);
    }

    public function show($route_id)
    {
        $route = Route::with(['agency', 'trips'])
            ->where('route_id', $route_id)
            ->first();

        if (!$route) {
            return response()->json(['error' => 'Route not found'], 404);
        }

        return $route;
    }

    public function trips($route_id)
    {
        $trips = \App\Models\Trip::with(['stopTimes.stop', 'calendar'])
            ->where('route_id', $route_id)
            ->get();

        if ($trips->isEmpty()) {
            return response()->json(['error' => 'No trips found for this route'], 404);
        }

        return $trips;
    }
}

