<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

class TripsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('trips')->delete();

        $rows = array_map('str_getcsv', file(database_path('seed_data/trips.txt')));
        array_shift($rows);

        foreach ($rows as $row) {
            Trip::create([
                'route_id' => $row[0],
                'service_id' => $row[1],
                'trip_id' => $row[2],
                'trip_headsign' => $row[3] ?: null,
                'block_id' => $row[4] ?: null,
                'shape_id' => $row[5] ?? null,
            ]);
        }
    }
}

