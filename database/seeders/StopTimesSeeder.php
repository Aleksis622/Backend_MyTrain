<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StopTime;
use Illuminate\Support\Facades\DB;

class StopTimesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('stop_times')->delete();

        $rows = array_map('str_getcsv', file(database_path('seed_data/stop_times.txt')));
        array_shift($rows);

        foreach ($rows as $row) {
            StopTime::create([
                'trip_id' => $row[0],
                'arrival_time' => $row[1],
                'departure_time' => $row[2],
                'stop_id' => $row[3],
                'stop_sequence' => (int)$row[4],
                'stop_headsign' => $row[5] ?? null,
                'pickup_type' => isset($row[6]) && $row[6] !== '' ? (int)$row[6] : 0,
                'drop_off_type' => isset($row[7]) && $row[7] !== '' ? (int)$row[7] : 0,
                'shape_dist_traveled' => $row[8] ?? null,
            ]);
        }
    }
}
