<?php

namespace Tests\Feature;

use App\Models\Journey;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TripStatus;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Concerns\SeedsTimetable;
use Tests\TestCase;

/**
 * Buying and paying. Stripe is faked with Http::fake; webhooks are signed the same way Stripe signs them.
 */
class TicketPaymentTest extends TestCase
{
    use RefreshDatabase, SeedsTimetable;

    private const WEBHOOK_SECRET = 'whsec_test_secret';

    private int $sessionCount = 0;

    // What GET /checkout/sessions/{id} returns (the server's own check with Stripe).
    private array $sessionState = ['status' => 'open', 'payment_status' => 'unpaid', 'payment_intent' => null];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTimetable();

        config(['services.stripe.secret' => 'sk_test_fake', 'services.stripe.webhook_secret' => self::WEBHOOK_SECRET]);
        Http::preventStrayRequests();
        Http::fake([
            'api.stripe.com/v1/checkout/sessions/*/expire' => Http::response(['status' => 'expired']),
            'api.stripe.com/v1/checkout/sessions/*' => fn () => Http::response(['id' => 'cs_test_1', ...$this->sessionState]),
            'api.stripe.com/v1/checkout/sessions' => function () {
                $id = 'cs_test_'.(++$this->sessionCount);

                return Http::response(['id' => $id, 'url' => "https://checkout.stripe.com/c/pay/{$id}", 'expires_at' => now()->addMinutes(31)->timestamp]);
            },
            'api.stripe.com/v1/refunds' => Http::response(['id' => 're_test_1', 'status' => 'succeeded']),
        ]);
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

    private function buyTicket(): Ticket
    {
        return Ticket::findOrFail($this->postJson('/api/tickets', $this->ticketRequest())->assertCreated()->json('ticket.id'));
    }

    private function startCheckout(Ticket $ticket): Payment
    {
        return Payment::findOrFail($this->postJson('/api/payments', ['ticket_id' => $ticket->id])->assertCreated()->json('payment.id'));
    }

    /**
     * Sends a Stripe "checkout.session.*" event for $payment, signed with $secret.
     */
    private function webhook(Payment $payment, string $type = 'checkout.session.completed', string $secret = self::WEBHOOK_SECRET, ?int $time = null): TestResponse
    {
        $payload = json_encode([
            'id' => 'evt_'.Str::random(10),
            'type' => $type,
            'data' => ['object' => [
                'id' => $payment->provider_session_id,
                'object' => 'checkout.session',
                'payment_status' => $type === 'checkout.session.completed' ? 'paid' : 'unpaid',
                'payment_intent' => "pi_test_{$payment->id}",
                'metadata' => ['payment_id' => (string) $payment->id, 'ticket_id' => (string) $payment->ticket_id],
            ]],
        ]);
        $time ??= time();
        $signature = hash_hmac('sha256', "{$time}.{$payload}", $secret);

        return $this->call('POST', '/api/webhooks/stripe', server: [
            'HTTP_STRIPE_SIGNATURE' => "t={$time},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], content: $payload);
    }

    private function stripeCalls(string $path): int
    {
        return Http::recorded(fn (HttpRequest $request) => str_ends_with($request->url(), $path))->count();
    }

    public function test_guests_cannot_buy_tickets(): void
    {
        $this->postJson('/api/tickets', $this->ticketRequest())->assertUnauthorized();
    }

    public function test_ticket_price_comes_from_fare_rules_not_the_client(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/tickets', $this->ticketRequest(['price' => '0.01']))
            ->assertCreated()
            ->assertJsonPath('ticket.price', '3.50')
            ->assertJsonPath('ticket.status', 'pending')
            ->assertJsonPath('ticket.journey.from_stop.stop_name', 'Riga');
    }

    public function test_ticket_without_fare_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/tickets', $this->ticketRequest(['to_stop_id' => 'S2']))
            ->assertUnprocessable()
            ->assertJsonPath('error', 'No fare is available for this connection.');
    }

    public function test_a_train_that_already_left_cannot_be_bought(): void
    {
        $this->travelTo(today()->setTime(10, 0)); // T1 leaves Riga at 08:00

        $this->actingAs(User::factory()->create());
        $this->postJson('/api/tickets', $this->ticketRequest(['date' => today()->toDateString()]))
            ->assertUnprocessable()
            ->assertJsonPath('error', 'This train has already left.');
        $this->postJson('/api/tickets', $this->ticketRequest())->assertCreated(); // tomorrow is fine
    }

    public function test_buying_the_same_train_again_returns_the_unpaid_ticket(): void
    {
        $this->actingAs(User::factory()->create());

        $first = $this->postJson('/api/tickets', $this->ticketRequest())->assertCreated()->json('ticket.id');
        $this->postJson('/api/tickets', $this->ticketRequest())->assertOk()->assertJsonPath('ticket.id', $first);

        $this->assertSame(1, Ticket::count());
        $this->assertSame(1, Journey::count());
    }

    public function test_unverified_users_cannot_buy_or_pay(): void
    {
        $this->actingAs(User::factory()->unverified()->create());

        $this->postJson('/api/tickets', $this->ticketRequest())->assertForbidden();
        $this->assertSame(0, Ticket::count());
    }

    public function test_full_stripe_payment_flow(): void
    {
        $this->actingAs(User::factory()->create());
        $ticket = $this->buyTicket();

        $response = $this->postJson('/api/payments', ['ticket_id' => $ticket->id])
            ->assertCreated()
            ->assertJsonPath('payment.status', 'pending')
            ->assertJsonPath('payment.amount', '3.50')
            ->assertJsonPath('checkout_url', 'https://checkout.stripe.com/c/pay/cs_test_1');
        $payment = Payment::findOrFail($response->json('payment.id'));

        // A second "Pay" click continues the same checkout page.
        $this->postJson('/api/payments', ['ticket_id' => $ticket->id])->assertJsonPath('payment.id', $payment->id);
        $this->assertSame(1, $this->stripeCalls('/checkout/sessions'));

        // The user cannot mark it paid any more: that endpoint is gone.
        $this->postJson("/api/payments/{$payment->id}/confirm")->assertNotFound();
        $this->assertSame(Ticket::PENDING, $ticket->fresh()->status);

        // Stripe's signed webhook does it.
        $this->webhook($payment)->assertOk();
        $this->assertSame(Payment::PAID, $payment->fresh()->status);
        $this->assertSame("pi_test_{$payment->id}", $payment->fresh()->provider_payment_id);
        $this->assertSame(Ticket::PAID, $ticket->fresh()->status);

        // Stripe retries webhooks: a repeat changes nothing.
        $this->webhook($payment)->assertOk();
        $this->assertSame(Payment::PAID, $payment->fresh()->status);

        $this->postJson("/api/tickets/{$ticket->id}/cancel")->assertUnprocessable(); // paid -> refund instead

        $this->postJson("/api/payments/{$payment->id}/refund")->assertOk()->assertJsonPath('payment.status', 'refunded');
        $this->assertSame(Ticket::REFUNDED, $ticket->fresh()->status);
        Http::assertSent(fn (HttpRequest $request) => str_ends_with($request->url(), '/refunds')
            && $request['payment_intent'] === "pi_test_{$payment->id}");
    }

    public function test_webhooks_without_a_valid_stripe_signature_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $payment = $this->startCheckout($this->buyTicket());

        $this->webhook($payment, secret: 'whsec_wrong')->assertStatus(400);
        $this->webhook($payment, time: time() - 3600)->assertStatus(400); // replayed old event
        $this->postJson('/api/webhooks/stripe', ['type' => 'checkout.session.completed'])->assertStatus(400);

        $this->assertSame(Payment::PENDING, $payment->fresh()->status);
        $this->assertSame(Ticket::PENDING, $payment->ticket->fresh()->status);
    }

    public function test_cancelled_ticket_cannot_become_paid_again(): void
    {
        $this->actingAs(User::factory()->create());
        $ticket = $this->buyTicket();
        $payment = $this->startCheckout($ticket);

        $this->postJson("/api/tickets/{$ticket->id}/cancel")->assertOk();
        $this->assertSame(Payment::CANCELLED, $payment->fresh()->status);
        $this->assertSame(1, $this->stripeCalls('/expire')); // the checkout page was closed

        // The money arrives anyway (the user paid at the same moment): it is sent straight back.
        $this->webhook($payment)->assertOk();
        $this->assertSame(Ticket::CANCELLED, $ticket->fresh()->status);
        $this->assertSame(Payment::REFUNDED, $payment->fresh()->status);
        $this->assertSame(1, $this->stripeCalls('/refunds'));

        $this->postJson('/api/payments', ['ticket_id' => $ticket->id])->assertUnprocessable();
    }

    public function test_a_ticket_never_has_two_active_payments(): void
    {
        $this->actingAs(User::factory()->create());
        $ticket = $this->buyTicket();

        // Cancel on Stripe's page, then try again: the old payment is closed, a new one starts.
        $first = $this->startCheckout($ticket);
        $this->postJson("/api/payments/{$first->id}/cancel")->assertOk()->assertJsonPath('payment.status', 'cancelled');
        $second = $this->startCheckout($ticket);
        $this->assertNotSame($first->id, $second->id);

        // Both pages get paid (two tabs): the first one wins, the second is refunded.
        $this->webhook($first->fresh())->assertOk();
        $this->webhook($second->fresh())->assertOk();

        $this->assertSame(Ticket::PAID, $ticket->fresh()->status);
        $this->assertSame(Payment::PAID, $first->fresh()->status);
        $this->assertSame(Payment::REFUNDED, $second->fresh()->status);
        $this->assertSame(1, $ticket->payments()->where('status', Payment::PAID)->count());

        // The database itself refuses a second active payment.
        $this->expectException(UniqueConstraintViolationException::class);
        $ticket->payments()->create([
            'user_id' => $ticket->user_id, 'provider' => 'stripe', 'amount' => 3.5, 'currency' => 'EUR', 'status' => Payment::PENDING,
        ]);
    }

    public function test_result_page_asks_stripe_when_the_webhook_is_late(): void
    {
        $this->actingAs(User::factory()->create());
        $payment = $this->startCheckout($this->buyTicket());

        $this->getJson("/api/payments/{$payment->id}")->assertOk()->assertJsonPath('status', 'pending');

        $this->sessionState = ['status' => 'complete', 'payment_status' => 'paid', 'payment_intent' => 'pi_test_late'];

        $this->getJson("/api/payments/{$payment->id}")
            ->assertOk()
            ->assertJsonPath('status', 'paid')
            ->assertJsonPath('ticket.status', 'paid');
    }

    public function test_passengers_can_refund_only_until_departure_unless_the_train_is_cancelled(): void
    {
        $this->actingAs(User::factory()->create());
        $ticket = $this->buyTicket();
        $payment = $this->startCheckout($ticket);
        $this->webhook($payment)->assertOk();

        $this->travelTo(now()->addDay()->setTime(9, 0)); // the 08:00 train has left

        $this->postJson("/api/payments/{$payment->id}/refund")
            ->assertUnprocessable()
            ->assertJsonPath('error', 'This train has already left. Please contact us for a refund.');

        TripStatus::create(['trip_id' => 'T1', 'service_date' => today()->toDateString(), 'status' => 'cancelled']);

        $this->postJson("/api/tickets/{$ticket->id}/refund")->assertOk()->assertJsonPath('ticket.status', 'refunded');
        $this->assertSame(Payment::REFUNDED, $payment->fresh()->status);
    }

    public function test_unpaid_tickets_are_cancelled_after_their_train_left(): void
    {
        $this->actingAs(User::factory()->create());
        $ticket = $this->buyTicket();
        $payment = $this->startCheckout($ticket);

        $this->travelTo(now()->addDay()->setTime(9, 0));
        $this->artisan('tickets:expire-unpaid')->assertSuccessful();

        $this->assertSame(Ticket::CANCELLED, $ticket->fresh()->status);
        $this->assertSame(Payment::CANCELLED, $payment->fresh()->status);
    }

    public function test_users_cannot_touch_other_users_tickets_or_payments(): void
    {
        $this->actingAs(User::factory()->create());
        $ticket = $this->buyTicket();
        $payment = $this->startCheckout($ticket);

        $this->actingAs(User::factory()->create());

        $this->getJson("/api/tickets/{$ticket->id}")->assertForbidden();
        $this->postJson("/api/tickets/{$ticket->id}/cancel")->assertForbidden();
        $this->postJson('/api/payments', ['ticket_id' => $ticket->id])->assertForbidden();
        $this->getJson("/api/payments/{$payment->id}")->assertForbidden();
        $this->postJson("/api/payments/{$payment->id}/cancel")->assertForbidden();
        $this->postJson("/api/payments/{$payment->id}/refund")->assertForbidden();
        $this->postJson("/api/tickets/{$ticket->id}/refund")->assertForbidden();
    }
}
