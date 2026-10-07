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

    public function test_train_details_for_icons_and_popup(): void
    {
        $this->travelTo(today()->setTime(8, 15)); // Riga -> Jurmala, which lies due west

        $this->getJson('/api/map/trains')
            ->assertOk()
            ->assertJsonPath('0.origin', 'Riga')
            ->assertJsonPath('0.destination', 'Tukums')
            ->assertJsonPath('0.destination_stop_id', 'S3')
            ->assertJsonPath('0.destination_arrival', '09:15:00')
            ->assertJsonPath('0.next_stop_id', 'S2')
            ->assertJsonPath('0.stop_number', 1)
            ->assertJsonPath('0.stops_total', 3)
            ->assertJsonPath('0.last_stop_sequence', 1)
            ->assertJsonPath('0.heading', fn ($heading) => $heading > 270 && $heading < 300);
    }

    public function test_train_at_station_knows_when_it_departs(): void
    {
        $this->travelTo(today()->setTime(8, 30, 30));

        $this->getJson('/api/map/trains')
            ->assertOk()
            ->assertJsonPath('0.current_stop_departure', '08:31:00')
            ->assertJsonPath('0.stop_number', 2)
            ->assertJsonPath('0.last_stop_sequence', 2);
    }

    public function test_train_route_lists_stops_with_names_and_times(): void
    {
        $this->getJson('/api/map/train-route/T1')
            ->assertOk()
            ->assertJsonPath('trip_id', 'T1')
            ->assertJsonPath('headsign', 'Tukums')
            ->assertJsonCount(3, 'stops')
            ->assertJsonPath('stops.0.stop_name', 'Riga')
            ->assertJsonPath('stops.0.longitude', 24.1213)
            ->assertJsonPath('stops.2.arrival_time', '09:15:00');

        $this->getJson('/api/map/train-route/NOPE')->assertNotFound();
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
