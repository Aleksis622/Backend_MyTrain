<?php

namespace Tests\Feature\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Tiny GTFS timetable: trip "T1" runs every day Riga (S1) -> Jurmala (S2) -> Tukums (S3).
 * Riga -> Tukums costs 3.50 EUR. There is no fare for Riga -> Jurmala.
 */
trait SeedsTimetable
{
    protected function seedTimetable(): void
    {
        DB::table('agencies')->insert(['agency_id' => 'vivi', 'agency_name' => 'Vivi']);
        DB::table('routes')->insert(['route_id' => 'R-1-V', 'agency_id' => 'vivi', 'route_long_name' => 'Riga - Tukums', 'route_type' => 2]);

        DB::table('stops')->insert([
            ['stop_id' => 'S1', 'stop_name' => 'Riga', 'stop_lat' => 56.9466, 'stop_lon' => 24.1213],
            ['stop_id' => 'S2', 'stop_name' => 'Jurmala', 'stop_lat' => 56.9680, 'stop_lon' => 23.7704],
            ['stop_id' => 'S3', 'stop_name' => 'Tukums', 'stop_lat' => 56.9671, 'stop_lon' => 23.1553],
        ]);

        DB::table('calendar')->insert([
            'service_id' => 'every_day',
            'monday' => 1, 'tuesday' => 1, 'wednesday' => 1, 'thursday' => 1,
            'friday' => 1, 'saturday' => 1, 'sunday' => 1,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);

        DB::table('trips')->insert(['trip_id' => 'T1', 'route_id' => 'R-1-V', 'service_id' => 'every_day', 'trip_headsign' => 'Tukums']);

        DB::table('stop_times')->insert([
            ['trip_id' => 'T1', 'stop_id' => 'S1', 'stop_sequence' => 1, 'arrival_time' => '08:00:00', 'departure_time' => '08:00:00'],
            ['trip_id' => 'T1', 'stop_id' => 'S2', 'stop_sequence' => 2, 'arrival_time' => '08:30:00', 'departure_time' => '08:31:00'],
            ['trip_id' => 'T1', 'stop_id' => 'S3', 'stop_sequence' => 3, 'arrival_time' => '09:15:00', 'departure_time' => '09:15:00'],
        ]);

        DB::table('fare_attributes')->insert(['fare_id' => 'F3_50', 'price' => 3.50, 'currency_type' => 'EUR', 'payment_method' => 1, 'agency_id' => 'vivi']);
        DB::table('fare_rules')->insert(['fare_id' => 'F3_50', 'origin_id' => 'S1', 'destination_id' => 'S3']);
    }
}
