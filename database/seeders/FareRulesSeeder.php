<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FareRule;
use Illuminate\Support\Facades\DB;

class FareRulesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('fare_rules')->delete();

        $rows = array_map('str_getcsv', file(database_path('seed_data/fare_rules.txt')));
        array_shift($rows);

        foreach ($rows as $row) {
            FareRule::create([
                'fare_id' => $row[0],
                'route_id' => $row[1] ?: null,
                'origin_id' => $row[2] ?: null,
                'destination_id' => $row[3] ?: null,
                'contains_id' => $row[4] ?: null,
            ]);
        }
    }
}

