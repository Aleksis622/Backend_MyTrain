<?php

namespace App\Services;

use App\Exceptions\PaymentProviderException;
use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

/**
 * The few Stripe API calls MyTrain needs (Stripe Checkout in test mode), plus the
 * webhook signature check. Keys come from .env: STRIPE_SECRET and STRIPE_WEBHOOK_SECRET.
 *
 * https://docs.stripe.com/api/checkout/sessions, https://docs.stripe.com/webhooks#verify-manually
 */
class StripeGateway
{
    private const API = 'https://api.stripe.com/v1';

    // Stripe's own minimum lifetime of a checkout page is 30 minutes.
    private const CHECKOUT_MINUTES = 31;

    // Webhook timestamps older than this are rejected (replay protection).
    private const WEBHOOK_TOLERANCE_SECONDS = 300;

    /**
     * Opens a Stripe Checkout page for one ticket. Returns the session (id, url, expires_at).
     */
    public function createCheckoutSession(Payment $payment, Ticket $ticket, string $locale): array
    {
        $journey = $ticket->journey;
        $frontend = rtrim(config('services.frontend.url'), '/');

        return $this->send('post', '/checkout/sessions', [
            'mode' => 'payment',
            'client_reference_id' => $payment->id,
            'customer_email' => $ticket->user->email,
            'locale' => in_array($locale, ['lv', 'ru', 'en'], true) ? $locale : 'auto',
            'expires_at' => now()->addMinutes(self::CHECKOUT_MINUTES)->timestamp,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($payment->currency),
                    'unit_amount' => (int) round($payment->amount * 100), // cents
                    'product_data' => [
                        'name' => "MyTrain: {$journey->fromStop->stop_name} → {$journey->toStop->stop_name}",
                        'description' => $journey->departure_time->format('d.m.Y H:i').' · '.$ticket->ticket_code,
                    ],
                ],
            ]],
            'metadata' => ['payment_id' => $payment->id, 'ticket_id' => $ticket->id],
            'payment_intent_data' => ['metadata' => ['payment_id' => $payment->id, 'ticket_id' => $ticket->id]],
            'success_url' => "{$frontend}/payment/{$payment->id}/result?session_id={CHECKOUT_SESSION_ID}",
            'cancel_url' => "{$frontend}/payment/{$payment->id}/result?cancelled=1",
        ], idempotencyKey: "checkout-payment-{$payment->id}");
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        return $this->send('get', "/checkout/sessions/{$sessionId}");
    }

    /**
     * Closes a checkout page so it can no longer be paid. Returns false when Stripe refuses
     * because the session is already complete (then it may have been paid: check it).
     */
    public function expireCheckoutSession(string $sessionId): bool
    {
        try {
            $this->send('post', "/checkout/sessions/{$sessionId}/expire");

            return true;
        } catch (PaymentProviderException) {
            return false;
        }
    }

    /**
     * Sends the money back. The idempotency key makes a repeated call refund only once.
     */
    public function refund(string $paymentIntentId, int $paymentId): array
    {
        return $this->send('post', '/refunds', ['payment_intent' => $paymentIntentId], idempotencyKey: "refund-payment-{$paymentId}");
    }

    /**
     * Checks the "Stripe-Signature" header (HMAC-SHA256 of "timestamp.body" with the webhook
     * secret) and returns the event. Anyone can call the webhook URL; only Stripe knows the secret.
     *
     * @throws UnexpectedValueException when the signature is missing, wrong or too old
     */
    public function verifyWebhook(string $payload, ?string $signatureHeader): array
    {
        $secret = config('services.stripe.webhook_secret');
        if (! $secret || ! $signatureHeader) {
            throw new UnexpectedValueException('Missing webhook secret or signature.');
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($key === 't') {
                $timestamp = (int) $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if (! $timestamp || abs(time() - $timestamp) > self::WEBHOOK_TOLERANCE_SECONDS) {
            throw new UnexpectedValueException('Webhook timestamp is missing or too old.');
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);
        $valid = collect($signatures)->contains(fn (string $signature) => hash_equals($expected, $signature));
        if (! $valid) {
            throw new UnexpectedValueException('Webhook signature does not match.');
        }

        $event = json_decode($payload, true);
        if (! is_array($event) || ! isset($event['type'])) {
            throw new UnexpectedValueException('Webhook body is not a Stripe event.');
        }

        return $event;
    }

    private function send(string $method, string $path, array $params = [], ?string $idempotencyKey = null): array
    {
        $secret = config('services.stripe.secret');
        if (! $secret) {
            Log::error('Stripe: STRIPE_SECRET is not set in .env');
            throw new PaymentProviderException('Stripe is not configured.');
        }

        try {
            $request = Http::withToken($secret)->asForm()->timeout(15);
            if ($idempotencyKey) {
                $request = $request->withHeaders(['Idempotency-Key' => $idempotencyKey]);
            }

            /** @var Response $response */
            $response = $request->{$method}(self::API.$path, $params);
        } catch (ConnectionException $e) {
            Log::error('Stripe: connection failed', ['path' => $path, 'error' => $e->getMessage()]);
            throw new PaymentProviderException('Stripe is not reachable.', previous: $e);
        }

        if ($response->failed()) {
            Log::error('Stripe: request refused', ['path' => $path, 'status' => $response->status(), 'error' => $response->json('error.message')]);
            throw new PaymentProviderException($response->json('error.message') ?? 'Stripe request failed.');
        }

        return $response->json();
    }
}
