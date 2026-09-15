<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrainController extends Controller
{
   // for now static popular stations
    public function popular()
    {
        return [
            [
                'id' => 1,
                'name' => 'Rīga → Jelgava',
                'description' => 'Fast trains every 30 minutes'
            ],
            [
                'id' => 2,
                'name' => 'Rīga → Sigulda',
                'description' => 'Popular scenic route'
            ],
            [
                'id' => 3,
                'name' => 'Rīga → Daugavpils',
                'description' => 'Long-distance express'
            ],
        ];
    }

   
    public function search(Request $request)
    {
        $request->validate([
            'from' => 'required|string',
            'to' => 'required|string',
        ]);

        // Default date + weekday
        $date = $request->date ?? date('Y-m-d');
        $weekday = $request->weekday ?? date('N');

        // Case-insensitive searching
        $from = strtolower($request->from);
        $to = strtolower($request->to);

        $query = DB::table('stop_times as st_from')
            ->join('stops as s_from', 's_from.stop_id', '=', 'st_from.stop_id')
            ->join('stop_times as st_to', 'st_to.trip_id', '=', 'st_from.trip_id')
            ->join('stops as s_to', 's_to.stop_id', '=', 'st_to.stop_id')
            ->join('trips', 'trips.trip_id', '=', 'st_from.trip_id')
            ->join('routes', 'routes.route_id', '=', 'trips.route_id')

            // matching case insensitive stations
            ->whereRaw('LOWER(s_from.stop_name) LIKE ?', ["%$from%"])
            ->whereRaw('LOWER(s_to.stop_name) LIKE ?', ["%$to%"])

            // Ensuring the correct direction - from -> to
            ->whereColumn('st_from.stop_sequence', '<', 'st_to.stop_sequence')

            ->select(
                'trips.trip_id',
                'trips.trip_headsign',
                'routes.route_long_name',
                's_from.stop_name as from_station',
                's_to.stop_name as to_station',
                'st_from.departure_time',
                'st_to.arrival_time'
            )
            ->orderBy('st_from.departure_time')
            ->limit(20)
            ->get();

        return $query;
    }
}
