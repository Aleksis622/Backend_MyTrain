<?php

namespace App\Http\Controllers;

use App\Models\StopTime;

class StopTimeController extends Controller
{
    public function index()
    {
        return StopTime::all();
    }

    public function show($id)
    {
        $time = StopTime::find($id);

        if (!$time) {
            return response()->json(['error' => 'Stop time not found'], 404);
        }

        return $time;
    }
}
