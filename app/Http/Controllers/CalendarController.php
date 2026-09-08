<?php

namespace App\Http\Controllers;

use App\Models\Calendar;

class CalendarController extends Controller
{
    public function index()
    {
        return Calendar::all();
    }

    public function show($service_id)
    {
        $calendar = Calendar::where('service_id', $service_id)->first();

        if (!$calendar) {
            return response()->json(['error' => 'Calendar not found'], 404);
        }

        return $calendar;
    }

    public function dates($service_id)
    {
        $dates = \App\Models\CalendarDate::where('service_id', $service_id)->get();

        if ($dates->isEmpty()) {
            return response()->json(['error' => 'No calendar dates found'], 404);
        }

        return $dates;
    }
}
