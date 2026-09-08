<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FareAttribute;
use Illuminate\Support\Facades\DB;

class FareAttributesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('fare_attributes')->delete();

        $rows = array_map('str_getcsv', file(database_path('seed_data/fare_attributes.txt')));
        array_shift($rows);

        foreach ($rows as $row) {
            FareAttribute::create([
                'fare_id' => $row[0],
                'price' => (float)$row[1],
                'currency_type' => $row[2],
                'payment_method' => (int)$row[3],
                'transfers' => $row[4] === '' ? null : (int)$row[4],
                'agency_id' => $row[5] ?: null,
                'transfer_duration' => $row[6] === '' ? null : (int)$row[6],
            ]);
        }
    }
}
