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

    public function test_search_skips_days_the_service_is_removed(): void
    {
        $date = now()->addDays(3)->toDateString();
        DB::table('calendar_dates')->insert(['service_id' => 'every_day', 'date' => $date, 'exception_type' => 2]);

        $this->getJson("/api/search-trains?from=riga&to=tukums&date={$date}")
            ->assertOk()
            ->assertJsonCount(0);
    }
}
