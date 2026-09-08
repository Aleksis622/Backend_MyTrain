<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Calendar;
use Illuminate\Support\Facades\DB;

class CalendarSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('calendar')->delete();

        $rows = array_map('str_getcsv', file(database_path('seed_data/calendar.txt')));
        array_shift($rows);

        foreach ($rows as $row) {
            Calendar::create([
                'service_id' => $row[0],
                'monday' => (int)$row[1],
                'tuesday' => (int)$row[2],
                'wednesday' => (int)$row[3],
                'thursday' => (int)$row[4],
                'friday' => (int)$row[5],
                'saturday' => (int)$row[6],
                'sunday' => (int)$row[7],
                'start_date' => $row[8],
                'end_date' => $row[9],
            ]);
        }
    }
}
