<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SeedsTimetable;
use Tests\TestCase;

class TicketPaymentTest extends TestCase
{
    use RefreshDatabase, SeedsTimetable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTimetable();
    }

    /**
     * @return array<string, string>
     */
    private function ticketRequest(array $overrides = []): array
    {
        return [
            'trip_id' => 'T1',
            'from_stop_id' => 'S1',
            'to_stop_id' => 'S3',
            'date' => now()->addDay()->toDateString(),
            ...$overrides,
        ];
    }

    public function test_guests_cannot_buy_tickets(): void
    {
        $this->postJson('/api/tickets', $this->ticketRequest())->assertUnauthorized();
    }

    public function test_ticket_price_comes_from_fare_rules_not_the_client(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/tickets', $this->ticketRequest(['price' => '0.01']))
            ->assertCreated()
            ->assertJsonPath('ticket.price', '3.50')
            ->assertJsonPath('ticket.status', 'pending')
            ->assertJsonPath('ticket.journey.from_stop.stop_name', 'Riga');
    }

    public function test_ticket_without_fare_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/tickets', $this->ticketRequest(['to_stop_id' => 'S2']))
            ->assertUnprocessable()
            ->assertJsonPath('error', 'No fare is available for this connection.');
    }

    public function test_full_payment_flow(): void
    {
        $user = User::factory()->create();
        $ticketId = $this->actingAs($user)->postJson('/api/tickets', $this->ticketRequest())->json('ticket.id');

        $paymentId = $this->postJson('/api/payments', ['ticket_id' => $ticketId, 'provider' => 'test'])
            ->assertCreated()
            ->assertJsonPath('payment.amount', '3.50')
            ->json('payment.id');

        // a second "Pay" click reuses the pending payment
        $this->postJson('/api/payments', ['ticket_id' => $ticketId, 'provider' => 'test'])
            ->assertJsonPath('payment.id', $paymentId);

        $this->postJson("/api/payments/{$paymentId}/confirm")->assertOk()->assertJsonPath('payment.status', 'paid');
        $this->assertSame('paid', Ticket::find($ticketId)->status);

        $this->postJson("/api/payments/{$paymentId}/confirm")->assertUnprocessable();
        $this->postJson("/api/tickets/{$ticketId}/cancel")->assertUnprocessable();

        $this->postJson("/api/payments/{$paymentId}/refund")->assertOk()->assertJsonPath('payment.status', 'refunded');
        $this->assertSame('cancelled', Ticket::find($ticketId)->status);
    }

    public function test_users_cannot_touch_other_users_tickets_or_payments(): void
    {
        $owner = User::factory()->create();
        $ticketId = $this->actingAs($owner)->postJson('/api/tickets', $this->ticketRequest())->json('ticket.id');
        $paymentId = $this->postJson('/api/payments', ['ticket_id' => $ticketId, 'provider' => 'test'])->json('payment.id');

        $this->actingAs(User::factory()->create());

        $this->getJson("/api/tickets/{$ticketId}")->assertForbidden();
        $this->postJson("/api/tickets/{$ticketId}/cancel")->assertForbidden();
        $this->postJson('/api/payments', ['ticket_id' => $ticketId, 'provider' => 'test'])->assertForbidden();
        $this->postJson("/api/payments/{$paymentId}/confirm")->assertForbidden();
    }
}
