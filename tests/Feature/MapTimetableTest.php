<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\SeedsTimetable;
use Tests\TestCase;

class MapTimetableTest extends TestCase
{
    use RefreshDatabase, SeedsTimetable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTimetable();
    }

    public function test_train_between_stations_is_placed_on_the_line_between_them(): void
    {
        $this->travelTo(today()->setTime(8, 15)); // halfway Riga 08:00 -> Jurmala 08:30

        $this->getJson('/api/map/trains')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.trip_id', 'T1')
            ->assertJsonPath('0.status', 'moving')
            ->assertJsonPath('0.previous_stop', 'Riga')
            ->assertJsonPath('0.next_stop', 'Jurmala')
            ->assertJsonPath('0.next_arrival', '08:30:00')
            ->assertJsonPath('0.latitude', round((56.9466 + 56.9680) / 2, 6))
            ->assertJsonPath('0.longitude', round((24.1213 + 23.7704) / 2, 6));
    }

    public function test_train_standing_at_a_station(): void
    {
        $this->travelTo(today()->setTime(8, 30, 30)); // Jurmala: arrives 08:30, leaves 08:31

        $this->getJson('/api/map/trains')
            ->assertOk()
            ->assertJsonPath('0.status', 'at_station')
            ->assertJsonPath('0.current_stop', 'Jurmala')
            ->assertJsonPath('0.next_stop', 'Tukums');
    }

    public function test_no_trains_outside_the_timetable(): void
    {
        $this->travelTo(today()->setTime(10, 0));

        $this->getJson('/api/map/trains')->assertOk()->assertJsonCount(0);
    }

    public function test_trip_from_yesterdays_timetable_running_past_midnight(): void
    {
        DB::table('trips')->insert(['trip_id' => 'NIGHT', 'route_id' => 'R-1-V', 'service_id' => 'every_day', 'trip_headsign' => 'Tukums']);
        DB::table('stop_times')->insert([
            ['trip_id' => 'NIGHT', 'stop_id' => 'S1', 'stop_sequence' => 1, 'arrival_time' => '23:50:00', 'departure_time' => '23:50:00'],
            ['trip_id' => 'NIGHT', 'stop_id' => 'S2', 'stop_sequence' => 2, 'arrival_time' => '24:20:00', 'departure_time' => '24:20:00'],
        ]);

        $this->travelTo(today()->setTime(0, 5)); // "24:05:00" on yesterday's service day

        $this->getJson('/api/map/trains')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.trip_id', 'NIGHT')
            ->assertJsonPath('0.status', 'moving');
    }
}
