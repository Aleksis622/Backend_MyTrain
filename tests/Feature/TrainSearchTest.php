<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\SeedsTimetable;
use Tests\TestCase;

class TrainSearchTest extends TestCase
{
    use RefreshDatabase, SeedsTimetable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTimetable();
    }

    public function test_station_autocomplete_returns_matching_stops(): void
    {
        $this->getJson('/api/stops?search=tuk')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.stop_id', 'S3')
            ->assertJsonPath('0.stop_name', 'Tukums');
    }

    public function test_search_finds_trips_by_station_name_with_price(): void
    {
        $this->getJson('/api/search-trains?from=riga&to=tukums')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.trip_id', 'T1')
            ->assertJsonPath('0.from_stop_id', 'S1')
            ->assertJsonPath('0.to_stop_id', 'S3')
            ->assertJsonPath('0.departure_time', '08:00:00')
            ->assertJsonPath('0.currency', 'EUR');
    }

    public function test_search_accepts_stop_ids(): void
    {
        $this->getJson('/api/search-trains?from=S1&to=S2')
            ->assertOk()
            ->assertJsonPath('0.to_station', 'Jurmala');
    }

    public function test_search_respects_travel_direction(): void
    {
        $this->getJson('/api/search-trains?from=tukums&to=riga')
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_search_filters_by_departure_time(): void
    {
        $this->getJson('/api/search-trains?from=riga&to=tukums&time=07:30')
            ->assertOk()
            ->assertJsonCount(1);

        $this->getJson('/api/search-trains?from=riga&to=tukums&time=08:01')
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_search_skips_days_the_service_is_removed(): void
    {
        $date = now()->addDays(3)->toDateString();
        DB::table('calendar_dates')->insert(['service_id' => 'every_day', 'date' => $date, 'exception_type' => 2]);

        $this->getJson("/api/search-trains?from=riga&to=tukums&date={$date}")
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_departure_board_lists_trains_with_final_destination(): void
    {
        $date = now()->addDay()->toDateString();

        $this->getJson("/api/stops/S1/departures?date={$date}")
            ->assertOk()
            ->assertJsonPath('stop.stop_name', 'Riga')
            ->assertJsonPath('after', null)
            ->assertJsonCount(1, 'departures')
            ->assertJsonPath('departures.0.trip_id', 'T1')
            ->assertJsonPath('departures.0.departure_time', '08:00:00')
            ->assertJsonPath('departures.0.destination_stop_id', 'S3')
            ->assertJsonPath('departures.0.destination', 'Tukums');
    }

    public function test_departure_board_skips_trains_that_end_at_the_station(): void
    {
        $date = now()->addDay()->toDateString();

        $this->getJson("/api/stops/S3/departures?date={$date}")
            ->assertOk()
            ->assertJsonCount(0, 'departures');
    }

    public function test_departure_board_respects_time_and_calendar(): void
    {
        $date = now()->addDays(3)->toDateString();

        $this->getJson("/api/stops/S2/departures?date={$date}&after=08:31")
            ->assertOk()
            ->assertJsonCount(1, 'departures');

        $this->getJson("/api/stops/S2/departures?date={$date}&after=08:32")
            ->assertOk()
            ->assertJsonCount(0, 'departures');

        DB::table('calendar_dates')->insert(['service_id' => 'every_day', 'date' => $date, 'exception_type' => 2]);

        $this->getJson("/api/stops/S1/departures?date={$date}")
            ->assertOk()
            ->assertJsonCount(0, 'departures');
    }

    public function test_departure_board_for_unknown_station_is_404(): void
    {
        $this->getJson('/api/stops/NOPE/departures')->assertNotFound();
    }

    public function test_map_stations_returns_every_station_with_numeric_coordinates(): void
    {
        $this->getJson('/api/map/stations')
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonPath('0.stop_name', 'Jurmala')
            ->assertJsonPath('0.stop_lat', 56.968);
    }
}
