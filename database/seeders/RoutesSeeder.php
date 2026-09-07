<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use App\Models\Route;

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
                'route_short_name' => $row[1],
                'route_long_name' => $row[2],
                'route_desc' => $row[3],
                'route_type' => $row[4],
                'route_url' => $row[5],
                'route_color' => $row[6],
                'route_text_color' => $row[7],
            ]);
        }
    }
}
