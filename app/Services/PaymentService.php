<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Exceptions\PaymentProviderException;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TripStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Payment lifecycle (see Payment::TRANSITIONS and Ticket::TRANSITIONS).
 *
 * Rules:
 * - Only Stripe can make a payment "paid" (signed webhook, or the server asking Stripe itself).
 * - Every change locks the ticket first, then its payments, inside one transaction,
 *   so a cancel and a confirmation can never both win.
 * - A ticket has at most one active (pending/paid) payment; the database enforces it too.
 * - Money that arrives for a ticket that can no longer be paid is refunded automatically.
 */
class PaymentService
{
    public function __construct(private StripeGateway $stripe) {}

    /**
     * Start (or continue) paying for a ticket: returns the pending payment with its Stripe checkout_url.
     */
    public function startCheckout(Ticket $ticket, string $locale): Payment
    {
        $payment = DB::transaction(function () use ($ticket) {
            $ticket = Ticket::lockForUpdate()->findOrFail($ticket->id);
            $this->ensurePayable($ticket);

            $open = $ticket->payments()->where('status', Payment::PENDING)->lockForUpdate()->first();
            if ($open?->hasOpenCheckout()) {
                return $open; // a second "Pay" click continues the same checkout page
            }
            if ($open) {
                $this->closePending($open, Payment::FAILED); // expired or unfinished: start a fresh one
            }

            return $ticket->payments()->create([
                'user_id' => $ticket->user_id,
                'provider' => Payment::STRIPE,
                'amount' => $ticket->price,
                'currency' => $ticket->currency,
                'status' => Payment::PENDING,
            ]);
        });

        if ($payment->checkout_url) {
            return $payment;
        }

        try {
            $session = $this->stripe->createCheckoutSession($payment, $ticket->load('journey.fromStop', 'journey.toStop', 'user'), $locale);
        } catch (PaymentProviderException $e) {
            $payment->moveTo(Payment::FAILED);
            throw $e;
        }

        $payment->update([
            'provider_session_id' => $session['id'],
            'checkout_url' => $session['url'],
            // Stripe sends a Unix timestamp; convert it to the app's time zone before saving.
            'checkout_expires_at' => Carbon::createFromTimestamp($session['expires_at'], config('app.timezone')),
        ]);

        return $payment;
    }

    /**
     * Stripe says the checkout was paid (webhook or our own check). Safe to call more than once.
     */
    public function markPaidByProvider(Payment $payment, string $paymentIntentId): Payment
    {
        DB::transaction(function () use ($payment, $paymentIntentId) {
            Ticket::lockForUpdate()->findOrFail($payment->ticket_id);
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            $ticket = $payment->ticket;

            if (in_array($payment->status, [Payment::PAID, Payment::REFUNDED], true)) {
                return; // already handled (Stripe sends webhooks again on retries)
            }

            $paid = ['provider_payment_id' => $paymentIntentId, 'paid_at' => now()];

            if ($ticket->status !== Ticket::PENDING) {
                // e.g. paid in a second tab after the ticket was already paid or cancelled:
                // the money really arrived, so it goes straight back.
                $refund = $this->stripe->refund($paymentIntentId, $payment->id);
                $payment->moveTo(Payment::REFUNDED, [...$paid, 'provider_refund_id' => $refund['id'] ?? null, 'refunded_at' => now()]);
                Log::warning('Late payment for a ticket that cannot be paid any more: refunded', ['payment_id' => $payment->id]);

                return;
            }

            // A different, newer checkout for the same ticket is still open: close it first,
            // so the one-active-payment rule holds.
            $ticket->payments()
                ->where('status', Payment::PENDING)
                ->whereKeyNot($payment->id)
                ->get()
                ->each(fn (Payment $other) => $this->closePending($other, Payment::CANCELLED));

            $payment->moveTo(Payment::PAID, $paid);
            $ticket->moveTo(Ticket::PAID);

            Log::info('Payment confirmed by Stripe', ['payment_id' => $payment->id, 'ticket_id' => $ticket->id]);
        });

        return $payment->refresh();
    }

    /**
     * Stripe says the checkout expired or failed.
     */
    public function markFailedByProvider(Payment $payment): Payment
    {
        DB::transaction(function () use ($payment) {
            Ticket::lockForUpdate()->findOrFail($payment->ticket_id);
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($payment->status === Payment::PENDING) {
                $payment->moveTo(Payment::FAILED);
            }
        });

        return $payment->refresh();
    }

    /**
     * The user left the Stripe page with "Back" / cancel.
     */
    public function cancelCheckout(Payment $payment): Payment
    {
        $stripeClosedIt = DB::transaction(function () use ($payment) {
            Ticket::lockForUpdate()->findOrFail($payment->ticket_id);
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== Payment::PENDING) {
                return true; // already paid / closed: nothing to cancel
            }

            return $this->closePending($payment, Payment::CANCELLED);
        });

        // Stripe would not close the page because it was completed meanwhile: take the real result.
        if (! $stripeClosedIt) {
            $this->syncFromProvider($payment->refresh());
        }

        return $payment->refresh();
    }

    /**
     * Ask Stripe directly how a pending checkout ended. Used by the result page, so the
     * app shows the truth even if a webhook is late or the webhook forwarder is not running.
     */
    public function syncFromProvider(Payment $payment): Payment
    {
        if ($payment->provider !== Payment::STRIPE || ! $payment->provider_session_id
            || ! in_array($payment->status, [Payment::PENDING, Payment::CANCELLED, Payment::FAILED], true)) {
            return $payment;
        }

        $session = $this->stripe->retrieveCheckoutSession($payment->provider_session_id);

        if (($session['payment_status'] ?? null) === 'paid') {
            return $this->markPaidByProvider($payment, (string) $session['payment_intent']);
        }
        if (($session['status'] ?? null) === 'expired') {
            return $this->markFailedByProvider($payment);
        }

        return $payment;
    }

    /**
     * Cancel every unfinished payment of a ticket that is being cancelled.
     * Must run inside the transaction that holds the ticket lock.
     */
    public function cancelPendingPaymentsOf(Ticket $ticket): void
    {
        $ticket->payments()
            ->where('status', Payment::PENDING)
            ->lockForUpdate()
            ->get()
            ->each(fn (Payment $payment) => $this->closePending($payment, Payment::CANCELLED));
    }

    /**
     * Paid at the ticket office: an admin records a manual payment.
     */
    public function recordManualPayment(Ticket $ticket, int $adminId): Payment
    {
        return DB::transaction(function () use ($ticket, $adminId) {
            $ticket = Ticket::lockForUpdate()->findOrFail($ticket->id);
            if ($ticket->status !== Ticket::PENDING) {
                throw new BookingException("Ticket is {$ticket->status} and cannot be paid.");
            }

            $this->cancelPendingPaymentsOf($ticket);

            $payment = $ticket->payments()->create([
                'user_id' => $ticket->user_id,
                'provider' => Payment::MANUAL,
                'provider_payment_id' => "admin-{$adminId}",
                'amount' => $ticket->price,
                'currency' => $ticket->currency,
                'status' => Payment::PAID,
                'paid_at' => now(),
            ]);
            $ticket->moveTo(Ticket::PAID);

            return $payment;
        });
    }

    /**
     * Passengers can refund until their train leaves, or any time if the railway cancelled it.
     */
    public function userMayRefund(Ticket $ticket): bool
    {
        $journey = $ticket->journey;

        if ($ticket->status !== Ticket::PAID || ! $journey) {
            return false;
        }

        if ($journey->departure_time->isFuture()) {
            return true;
        }

        return TripStatus::where('trip_id', $journey->trip_id)
            ->whereDate('service_date', $journey->departure_time)
            ->where('status', TripStatus::CANCELLED)
            ->exists();
    }

    /**
     * Refund a paid payment: Stripe sends the money back, then payment and ticket become "refunded".
     * byAdmin: admins may refund at any time (e.g. after departure, on a complaint).
     */
    public function refund(Payment $payment, bool $byAdmin = false): Payment
    {
        $payment = DB::transaction(function () use ($payment, $byAdmin) {
            $ticket = Ticket::lockForUpdate()->findOrFail($payment->ticket_id);
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== Payment::PAID || $ticket->status !== Ticket::PAID) {
                throw new BookingException('Only paid tickets can be refunded.');
            }
            if (! $byAdmin && ! $this->userMayRefund($ticket)) {
                throw new BookingException('This train has already left. Please contact us for a refund.');
            }

            // Inside the lock, so two refund clicks cannot both reach Stripe
            // (and the idempotency key would make Stripe refund only once anyway).
            // Manual (ticket office) payments are paid back in cash, so only the status changes.
            $refundId = $payment->provider === Payment::STRIPE
                ? ($this->stripe->refund($payment->provider_payment_id, $payment->id)['id'] ?? null)
                : null;

            $payment->moveTo(Payment::REFUNDED, ['provider_refund_id' => $refundId, 'refunded_at' => now()]);
            $ticket->moveTo(Ticket::REFUNDED);

            return $payment;
        });

        Log::warning('Payment refunded', ['payment_id' => $payment->id, 'ticket_id' => $payment->ticket_id, 'by_admin' => $byAdmin]);

        return $payment->refresh();
    }

    /**
     * Close an unfinished payment and its Stripe page. Returns false when Stripe refused
     * to close the page (it was completed meanwhile).
     */
    private function closePending(Payment $payment, string $status): bool
    {
        $closed = true;
        if ($payment->provider_session_id && $payment->checkout_expires_at?->isFuture()) {
            $closed = $this->stripe->expireCheckoutSession($payment->provider_session_id);
        }

        $payment->moveTo($status);

        return $closed;
    }

    private function ensurePayable(Ticket $ticket): void
    {
        if ($ticket->status !== Ticket::PENDING) {
            throw new BookingException("Ticket is {$ticket->status} and cannot be paid.");
        }
        if ($ticket->journey && $ticket->journey->departure_time->isPast()) {
            throw new BookingException('This train has already left.');
        }
    }
}
