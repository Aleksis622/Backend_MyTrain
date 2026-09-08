<?php

namespace App\Http\Controllers;

use App\Models\Route;

class RouteController extends Controller
{
    public function index()
    {
        return Route::all();
    }

    public function show($route_id)
    {
        $route = Route::where('route_id', $route_id)->first();

        if (!$route) {
            return response()->json(['error' => 'Route not found'], 404);
        }

        return $route;
    }

    public function trips($route_id)
    {
        $trips = \App\Models\Trip::where('route_id', $route_id)->get();

        if ($trips->isEmpty()) {
            return response()->json(['error' => 'No trips found for this route'], 404);
        }

        return $trips;
    }
}
