<?php

namespace App\Http\Controllers;

use App\Models\Journey;

class JourneyController extends Controller
{
    public function index()
    {
        return Journey::with([
                'train',
                'fromStop',
                'toStop',
                'trip.route',
                'trip.calendar',
                'trip.calendarDates'
            ])
            ->paginate(20);
    }

    public function show($id)
    {
        return Journey::with([
                'train',
                'fromStop',
                'toStop',
                'trip.route',
                'trip.stopTimes.stop',
                'trip.calendar',
                'trip.calendarDates'
            ])
            ->findOrFail($id);
    }
}
