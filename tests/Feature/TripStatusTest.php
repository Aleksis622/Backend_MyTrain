<?php

namespace Tests\Feature;

use App\Models\TripStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SeedsTimetable;
use Tests\TestCase;

class TripStatusTest extends TestCase
{
    use RefreshDatabase, SeedsTimetable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTimetable();
    }

    private function setStatus(string $status, int $minutes = 0, ?string $date = null): void
    {
        TripStatus::create([
            'trip_id' => 'T1',
            'service_date' => $date ?? today()->toDateString(),
            'status' => $status,
            'delay_minutes' => $minutes,
            'reason' => 'Signal fault',
        ]);
    }

    public function test_search_shows_trains_on_time_by_default(): void
    {
        $this->getJson('/api/search-trains?from=riga&to=tukums')
            ->assertOk()
            ->assertJsonPath('0.status', 'on_time')
            ->assertJsonPath('0.delay_minutes', 0)
            ->assertJsonPath('0.status_reason', null);
    }

    public function test_search_shows_delay_and_reason(): void
    {
        $this->setStatus(TripStatus::DELAYED, 12);

        $this->getJson('/api/search-trains?from=riga&to=tukums')
            ->assertOk()
            ->assertJsonPath('0.status', 'delayed')
            ->assertJsonPath('0.delay_minutes', 12)
            ->assertJsonPath('0.status_reason', 'Signal fault');
    }

    public function test_status_only_applies_to_its_own_day(): void
    {
        $this->setStatus(TripStatus::CANCELLED, date: today()->addDay()->toDateString());

        $this->getJson('/api/search-trains?from=riga&to=tukums')->assertJsonPath('0.status', 'on_time');
        $this->getJson('/api/search-trains?from=riga&to=tukums&date='.today()->addDay()->toDateString())
            ->assertJsonPath('0.status', 'cancelled');
    }

    public function test_cancelled_train_cannot_be_bought(): void
    {
        $date = today()->addDay()->toDateString();
        $this->setStatus(TripStatus::CANCELLED, date: $date);

        $this->actingAs(User::factory()->create())
            ->postJson('/api/tickets', ['trip_id' => 'T1', 'from_stop_id' => 'S1', 'to_stop_id' => 'S3', 'date' => $date])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'This train is cancelled on the selected date.');
    }

    public function test_departure_board_shows_status(): void
    {
        $date = today()->addDay()->toDateString();
        $this->setStatus(TripStatus::DELAYED, 5, $date);

        $this->getJson("/api/stops/S1/departures?date={$date}")
            ->assertOk()
            ->assertJsonPath('departures.0.status', 'delayed')
            ->assertJsonPath('departures.0.delay_minutes', 5);
    }

    public function test_map_places_delayed_train_where_the_timetable_had_it_earlier(): void
    {
        $this->setStatus(TripStatus::DELAYED, 15);
        $this->travelTo(today()->setTime(8, 15)); // 15 min late = still at Riga (departs 08:00)

        $this->getJson('/api/map/trains')
            ->assertOk()
            ->assertJsonPath('0.status', 'at_station')
            ->assertJsonPath('0.current_stop', 'Riga')
            ->assertJsonPath('0.service_status', 'delayed')
            ->assertJsonPath('0.delay_minutes', 15)
            ->assertJsonPath('0.status_reason', 'Signal fault');
    }

    public function test_map_keeps_a_late_train_after_its_timetable_ended(): void
    {
        $this->setStatus(TripStatus::DELAYED, 10);
        $this->travelTo(today()->setTime(9, 20)); // timetable ended 09:15, the late train arrives 09:25

        $this->getJson('/api/map/trains')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.status', 'moving')
            ->assertJsonPath('0.next_stop', 'Tukums');
    }

    public function test_map_hides_cancelled_trains(): void
    {
        $this->setStatus(TripStatus::CANCELLED);
        $this->travelTo(today()->setTime(8, 15));

        $this->getJson('/api/map/trains')->assertOk()->assertJsonCount(0);
    }

    public function test_status_command_sets_and_clears_a_status(): void
    {
        $this->artisan('trains:status', ['trip_id' => 'T1', 'status' => 'delayed', '--minutes' => 7, '--reason' => 'Track works'])
            ->assertSuccessful();
        $this->assertDatabaseHas('trip_statuses', ['trip_id' => 'T1', 'status' => 'delayed', 'delay_minutes' => 7]);

        $this->artisan('trains:status', ['trip_id' => 'T1', 'status' => 'on_time'])->assertSuccessful();
        $this->assertDatabaseCount('trip_statuses', 0);

        $this->artisan('trains:status', ['trip_id' => 'T1', 'status' => 'delayed'])->assertFailed();
        $this->artisan('trains:status', ['trip_id' => 'NOPE', 'status' => 'cancelled'])->assertFailed();
    }

    public function test_random_status_command_creates_demo_disruptions(): void
    {
        $this->travelTo(today()->setTime(7, 0));

        $this->artisan('trains:random-status', ['--delayed' => 0, '--cancelled' => 1])->assertSuccessful();

        $this->assertDatabaseHas('trip_statuses', ['trip_id' => 'T1', 'status' => 'cancelled']);
    }
}
