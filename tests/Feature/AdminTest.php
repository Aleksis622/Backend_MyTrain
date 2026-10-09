<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\Ticket;
use App\Models\TripStatus;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Concerns\SeedsTimetable;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase, SeedsTimetable;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTimetable();
        $this->admin = User::factory()->create();
        $this->admin->forceFill(['is_admin' => true])->save();
    }

    private function buyTicket(User $user): Ticket
    {
        $id = $this->actingAs($user)->postJson('/api/tickets', [
            'trip_id' => 'T1', 'from_stop_id' => 'S1', 'to_stop_id' => 'S3', 'date' => today()->addDay()->toDateString(),
        ])->json('ticket.id');

        return Ticket::findOrFail($id);
    }

    public function test_admin_routes_are_closed_to_guests_and_normal_users(): void
    {
        $this->getJson('/api/admin/dashboard')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->getJson('/api/admin/dashboard')->assertForbidden();
        $this->actingAs($this->admin)->getJson('/api/admin/dashboard')->assertOk();
    }

    public function test_user_cannot_make_themselves_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/user/language', ['language' => 'en', 'is_admin' => true]);

        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_seeder_creates_the_premade_admin_from_config(): void
    {
        config(['services.admin.email' => 'boss@mytrain.lv', 'services.admin.password' => 'secret-pass']);

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class); // twice = still one account

        $this->assertSame(1, User::where('email', 'boss@mytrain.lv')->count());
        $this->assertTrue(User::firstWhere('email', 'boss@mytrain.lv')->is_admin);
    }

    public function test_dashboard_numbers(): void
    {
        $this->buyTicket(User::factory()->create());
        TripStatus::create(['trip_id' => 'T1', 'service_date' => today()->toDateString(), 'status' => 'delayed', 'delay_minutes' => 5]);

        $this->actingAs($this->admin)->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('users', 1)
            ->assertJsonPath('tickets_today', 1)
            ->assertJsonPath('tickets_by_status.pending', 1)
            ->assertJsonPath('delayed_today', 1)
            ->assertJsonPath('disruptions_today.0.trip_id', 'T1')
            ->assertJsonPath('disruptions_today.0.delay_minutes', 5)
            ->assertJsonCount(7, 'tickets_last_7_days')
            ->assertJsonPath('tickets_last_7_days.6', ['date' => today()->toDateString(), 'total' => 1])
            ->assertJsonCount(1, 'latest_tickets');
    }

    public function test_admin_can_search_edit_and_delete_users(): void
    {
        $anna = User::factory()->create(['name' => 'Anna Liepa', 'email' => 'anna@example.com']);
        User::factory()->create(['name' => 'Juris']);

        $this->actingAs($this->admin)->getJson('/api/admin/users?search=liepa')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.email', 'anna@example.com');

        $this->putJson("/api/admin/users/{$anna->id}", ['name' => 'Anna Kalna', 'email' => 'anna@example.com', 'language' => 'en'])
            ->assertOk();
        $this->assertSame('Anna Kalna', $anna->fresh()->name);

        $this->deleteJson("/api/admin/users/{$anna->id}")->assertOk();
        $this->assertNull(User::find($anna->id));

        $this->deleteJson("/api/admin/users/{$this->admin->id}")->assertUnprocessable();
    }

    public function test_admin_ticket_actions_follow_the_payment_rules(): void
    {
        $ticket = $this->buyTicket(User::factory()->create());

        $this->actingAs($this->admin);

        $this->getJson('/api/admin/tickets?status=pending')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/admin/tickets?search='.$ticket->ticket_code)->assertJsonPath('total', 1);
        $this->getJson('/api/admin/tickets?date='.today()->addDay()->toDateString())->assertJsonPath('total', 1);
        $this->getJson('/api/admin/tickets?date='.today()->toDateString())->assertJsonPath('total', 0);

        $this->postJson("/api/admin/tickets/{$ticket->id}/refund")->assertUnprocessable(); // nothing paid yet

        $this->postJson("/api/admin/tickets/{$ticket->id}/mark-paid")
            ->assertOk()
            ->assertJsonPath('ticket.status', 'paid')
            ->assertJsonPath('ticket.payments.0.provider', 'manual');

        $this->postJson("/api/admin/tickets/{$ticket->id}/cancel")->assertUnprocessable(); // paid -> refund instead

        $this->postJson("/api/admin/tickets/{$ticket->id}/refund")
            ->assertOk()
            ->assertJsonPath('ticket.status', 'refunded')
            ->assertJsonPath('ticket.payments.0.status', 'refunded');
    }

    public function test_admin_sets_train_status_but_timetable_is_untouched(): void
    {
        $date = today()->toDateString();
        $stopTimesBefore = DB::table('stop_times')->get();

        $this->actingAs($this->admin)->getJson("/api/admin/trains?date={$date}")
            ->assertOk()
            ->assertJsonPath('trips.0.trip_id', 'T1')
            ->assertJsonPath('trips.0.origin', 'Riga')
            ->assertJsonPath('trips.0.destination', 'Tukums')
            ->assertJsonPath('trips.0.status', 'on_time');

        $this->putJson('/api/admin/trains/T1/status', ['date' => $date, 'status' => 'delayed'])->assertUnprocessable();

        $this->putJson('/api/admin/trains/T1/status', ['date' => $date, 'status' => 'delayed', 'minutes' => 12, 'reason' => 'Track works'])
            ->assertOk()
            ->assertJsonPath('delay_minutes', 12);
        $this->getJson("/api/admin/trains?date={$date}")->assertJsonPath('trips.0.status', 'delayed');

        $this->putJson('/api/admin/trains/T1/status', ['date' => $date, 'status' => 'on_time'])->assertOk();
        $this->assertDatabaseCount('trip_statuses', 0);

        $this->assertEquals($stopTimesBefore, DB::table('stop_times')->get());
    }

    public function test_admin_actions_are_written_to_the_activity_log(): void
    {
        $passenger = User::factory()->create(['name' => 'Anna Liepa']);
        $ticket = $this->buyTicket($passenger);

        $this->actingAs($this->admin);
        $this->postJson("/api/admin/tickets/{$ticket->id}/cancel")->assertOk();
        $this->putJson("/api/admin/users/{$passenger->id}", ['name' => 'Anna Kalna', 'email' => $passenger->email, 'language' => 'lv'])->assertOk();
        $this->putJson('/api/admin/trains/T1/status', ['date' => today()->toDateString(), 'status' => 'cancelled', 'reason' => 'Storm'])->assertOk();

        $this->getJson('/api/admin/activity')
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('data.0.action', AdminAction::TRAIN_STATUS_CHANGED)
            ->assertJsonPath('data.0.details.reason', 'Storm')
            ->assertJsonPath('data.0.admin.id', $this->admin->id)
            ->assertJsonPath('data.1.action', AdminAction::USER_UPDATED)
            ->assertJsonPath('data.1.details.fields', ['name'])
            ->assertJsonPath('data.2.action', AdminAction::TICKET_CANCELLED)
            ->assertJsonPath('data.2.details.ticket_code', $ticket->ticket_code);

        $this->getJson('/api/admin/activity?type=ticket')->assertJsonPath('total', 1);
        $this->getJson('/api/admin/activity?type=nonsense')->assertUnprocessable();

        // Saving without changes is not an action.
        $this->putJson("/api/admin/users/{$passenger->id}", ['name' => 'Anna Kalna', 'email' => $passenger->email, 'language' => 'lv'])->assertOk();
        $this->assertDatabaseCount('admin_actions', 3);
    }

    public function test_admin_can_edit_own_account_and_change_password(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/api/admin/account')->assertForbidden();

        $this->actingAs($this->admin)->getJson('/api/admin/account')
            ->assertOk()
            ->assertJsonPath('user.id', $this->admin->id)
            ->assertJsonPath('actions_total', 0);

        $this->putJson('/api/admin/account', ['name' => 'Chief', 'email' => 'chief@mytrain.lv'])->assertOk();
        $this->assertSame('chief@mytrain.lv', $this->admin->fresh()->email);

        $this->putJson('/api/admin/account/password', [
            'current_password' => 'wrong', 'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->putJson('/api/admin/account/password', [
            'current_password' => 'password', 'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1',
        ])->assertOk();
        $this->assertTrue(Hash::check('new-secret-1', $this->admin->fresh()->password));

        $this->getJson('/api/admin/account')
            ->assertJsonPath('actions_total', 2)
            ->assertJsonPath('recent_actions.0.action', AdminAction::PASSWORD_CHANGED);
    }

    public function test_user_detail_shows_trip_statistics(): void
    {
        $passenger = User::factory()->create();
        $ticket = $this->buyTicket($passenger);
        $this->actingAs($this->admin)->postJson("/api/admin/tickets/{$ticket->id}/mark-paid")->assertOk();

        $this->getJson("/api/admin/users/{$passenger->id}")
            ->assertOk()
            ->assertJsonPath('stats.trips_taken', 0)
            ->assertJsonPath('stats.upcoming_trips', 1)
            ->assertJsonPath('stats.total_spent', (float) $ticket->price)
            ->assertJsonPath('actions', []);

        $this->getJson("/api/admin/users/{$this->admin->id}")->assertJsonCount(1, 'actions');
    }
}
