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


        $handle = fopen(database_path('seed_data/stop_times.txt'), 'r');
        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            StopTime::create([
                'trip_id' => $row[0],
                'arrival_time' => $row[1],
                'departure_time' => $row[2],
                'stop_id' => $row[3],
                'stop_sequence' => $row[4],
                'pickup_type' => $row[5],
                'drop_off_type' => $row[6],
            ]);
        }

        fclose($handle);
    }
}
