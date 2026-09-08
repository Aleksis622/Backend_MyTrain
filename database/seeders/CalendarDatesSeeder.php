<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CalendarDate;
use Illuminate\Support\Facades\DB;

class CalendarDatesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('calendar_dates')->delete();

        $rows = array_map('str_getcsv', file(database_path('seed_data/calendar_dates.txt')));
        array_shift($rows);

        foreach ($rows as $row) {
            CalendarDate::create([
                'service_id' => $row[0],
                'date' => $row[1],
                'exception_type' => (int)$row[2],
            ]);
        }
    }
}
