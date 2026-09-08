<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agency;
use Illuminate\Support\Facades\DB;

class AgencySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('agencies')->delete();

        $rows = array_map('str_getcsv', file(database_path('seed_data/agency.txt')));
        array_shift($rows);

        foreach ($rows as $row) {
            Agency::create([
                'agency_id' => $row[0],
                'agency_name' => $row[1],
                'agency_url' => $row[2] ?: null,
                'agency_timezone' => $row[3],
                'agency_lang' => $row[4] ?: null,
                'agency_phone' => $row[5] ?: null,
                'agency_fare_url' => $row[6] ?: null,
                'agency_email' => $row[7] ?: null,
            ]);
        }
    }
}
