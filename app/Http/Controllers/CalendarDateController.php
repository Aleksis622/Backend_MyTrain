<?php

namespace App\Http\Controllers;

use App\Models\CalendarDate;

class CalendarDateController extends Controller
{
    public function index()
    {
        return CalendarDate::all();
    }

    public function show($service_id)
    {
        $dates = CalendarDate::where('service_id', $service_id)->get();

        if ($dates->isEmpty()) {
            return response()->json(['error' => 'No calendar dates found'], 404);
        }

        return $dates;
    }
}
