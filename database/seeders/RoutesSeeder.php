<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Route;
use Illuminate\Support\Facades\DB;

class RoutesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('routes')->delete();

        $rows = array_map('str_getcsv', file(database_path('seed_data/routes.txt')));
        array_shift($rows);

        foreach ($rows as $row) {
            Route::create([
                'route_id' => $row[0],
                'agency_id' => $row[1] ?: null,
                'route_short_name' => $row[2],
                'route_long_name' => $row[3],
                'route_desc' => $row[4] ?: null,
                'route_type' => (int)$row[5],
                'route_url' => $row[6] ?: null,
                'route_color' => $row[7] ?: null,
                'route_text_color' => $row[8] ?? null,
            ]);
        }
    }
}
