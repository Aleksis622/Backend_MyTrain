<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Stop;
use Illuminate\Support\Facades\DB;

class StopsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('stops')->delete();

        $rows = array_map('str_getcsv', file(database_path('seed_data/stops.txt')));
        array_shift($rows);

        foreach ($rows as $row) {
            Stop::create([
                'stop_id' => $row[0],
                'stop_code' => $row[1] ?: null,
                'stop_name' => $row[2],
                'stop_desc' => $row[3] ?: null,
                'stop_lat' => (float)$row[4],
                'stop_lon' => (float)$row[5],
                'zone_id' => $row[6] ?: null,
                'stop_url' => $row[7] ?: null,
                'location_type' => $row[8] === '' ? 0 : (int)$row[8],
                'parent_station' => $row[9] ?: null,
            ]);
        }
    }
}
