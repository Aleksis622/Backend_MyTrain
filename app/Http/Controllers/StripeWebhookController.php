<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\StripeGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

/**
 * POST /api/webhooks/stripe: Stripe tells us how a checkout ended.
 * The URL is public, so every request must carry a valid Stripe signature;
 * anything else is rejected before it can change a payment.
 *
 * Locally: stripe listen --forward-to localhost:8000/api/webhooks/stripe
 */
class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeGateway $stripe, PaymentService $payments): JsonResponse
    {
        try {
            $event = $stripe->verifyWebhook($request->getContent(), $request->header('Stripe-Signature'));
        } catch (UnexpectedValueException $e) {
            Log::warning('Stripe webhook rejected', ['reason' => $e->getMessage(), 'ip' => $request->ip()]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $session = $event['data']['object'] ?? [];
        $payment = $this->paymentFor($session);

        if (! $payment) {
            // not one of ours (other test events on the same Stripe account): acknowledge and ignore
            return response()->json(['received' => true]);
        }

        match ($event['type']) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded' => ($session['payment_status'] ?? null) === 'paid'
                ? $payments->markPaidByProvider($payment, (string) $session['payment_intent'])
                : null, // e.g. a bank transfer still on its way: async_payment_succeeded comes later
            'checkout.session.expired', 'checkout.session.async_payment_failed' => $payments->markFailedByProvider($payment),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    /**
     * The payment this checkout session belongs to (by our metadata and the stored session id).
     */
    private function paymentFor(array $session): ?Payment
    {
        $paymentId = $session['metadata']['payment_id'] ?? null;
        $sessionId = $session['id'] ?? null;

        if (! $paymentId || ! $sessionId) {
            return null;
        }

        return Payment::whereKey($paymentId)->where('provider_session_id', $sessionId)->first();
    }
}
