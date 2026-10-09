<?php

namespace Tests\Feature;

use App\Models\Journey;
use App\Models\Ticket;
use App\Models\TripStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SeedsTimetable;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase, SeedsTimetable;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTimetable();
        $this->user = User::factory()->create();
    }

    /**
     * A ticket on trip T1 (Riga -> Tukums) leaving at $departure.
     */
    private function ticket(string $status, string $departure): Ticket
    {
        // Tickets for the same train share one journey row (it is unique).
        $journey = Journey::firstOrCreate([
            'trip_id' => 'T1',
            'from_stop_id' => 'S1',
            'to_stop_id' => 'S3',
            'departure_time' => $departure,
        ], [
            'arrival_time' => now()->parse($departure)->addMinutes(75),
        ]);

        return $this->user->tickets()->create([
            'journey_id' => $journey->id,
            'ticket_code' => 'MT-'.fake()->unique()->numerify('####'),
            'price' => 3.50,
            'currency' => 'EUR',
            'status' => $status,
            'purchased_at' => now(),
        ]);
    }

    public function test_tickets_are_split_into_upcoming_past_and_cancelled(): void
    {
        $later = $this->ticket('paid', now()->addDays(3)->setTime(8, 0));
        $soon = $this->ticket('pending', now()->addDay()->setTime(8, 0));
        $old = $this->ticket('paid', now()->subDays(2)->setTime(8, 0));
        $cancelled = $this->ticket('cancelled', now()->addDay()->setTime(8, 0));

        $this->actingAs($this->user);

        $this->getJson('/api/tickets?scope=upcoming')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('data.0.id', $soon->id) // soonest first
            ->assertJsonPath('data.1.id', $later->id);
        $this->getJson('/api/tickets?scope=past')->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $old->id);
        $this->getJson('/api/tickets?scope=cancelled')->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $cancelled->id);
        $this->getJson('/api/tickets')->assertJsonPath('total', 4);
        $this->getJson('/api/tickets?scope=everything')->assertUnprocessable();
    }

    public function test_tickets_show_if_their_train_is_delayed(): void
    {
        $departure = now()->addDay()->setTime(8, 0);
        $this->ticket('paid', $departure);
        TripStatus::create(['trip_id' => 'T1', 'service_date' => $departure->toDateString(), 'status' => 'delayed', 'delay_minutes' => 15, 'reason' => 'Signal fault']);

        $this->actingAs($this->user)->getJson('/api/tickets?scope=upcoming')
            ->assertJsonPath('data.0.train_status.status', 'delayed')
            ->assertJsonPath('data.0.train_status.delay_minutes', 15)
            ->assertJsonPath('data.0.train_status.reason', 'Signal fault');
    }

    public function test_overview_has_stats_counts_and_the_next_paid_trip(): void
    {
        $this->ticket('pending', now()->addHours(30)); // unpaid: not the next trip
        $next = $this->ticket('paid', now()->addDays(2)->setTime(8, 0));
        $this->ticket('paid', now()->addDays(5)->setTime(8, 0));
        $this->ticket('paid', now()->subDay()->setTime(8, 0));
        $this->ticket('cancelled', now()->addDay()->setTime(8, 0));

        $this->actingAs($this->user)->getJson('/api/profile/overview')
            ->assertOk()
            ->assertJsonPath('stats.trips_taken', 1)
            ->assertJsonPath('stats.upcoming_trips', 2)
            ->assertJsonPath('counts', ['upcoming' => 3, 'past' => 1, 'cancelled' => 1, 'unpaid' => 1])
            ->assertJsonPath('next_trip.id', $next->id)
            ->assertJsonPath('next_trip.journey.to_stop.stop_name', 'Tukums')
            ->assertJsonPath('next_trip.train_status.status', 'on_time');
    }

    public function test_overview_without_tickets_and_for_guests(): void
    {
        $this->getJson('/api/profile/overview')->assertUnauthorized();

        $this->actingAs($this->user)->getJson('/api/profile/overview')
            ->assertOk()
            ->assertJsonPath('next_trip', null)
            ->assertJsonPath('counts.upcoming', 0);
    }
}
