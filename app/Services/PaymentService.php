<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Payment lifecycle: pending -> paid -> refunded.
 * The ticket follows along: pending -> paid -> cancelled.
 */
class PaymentService
{
    /**
     * Start paying for a ticket. Re-uses an existing pending payment so double clicks don't create duplicates.
     */
    public function createPendingPayment(Ticket $ticket, string $provider): Payment
    {
        if ($ticket->status !== 'pending') {
            throw new BookingException("Ticket is {$ticket->status} and cannot be paid.");
        }

        return $ticket->payments()->firstOrCreate(
            ['status' => 'pending'],
            [
                'user_id' => $ticket->user_id,
                'provider' => $provider,
                'amount' => $ticket->price,
                'currency' => $ticket->currency,
            ],
        );
    }

    /**
     * Mark a payment as paid. In production this should be called from the
     * payment provider's webhook after verifying its signature, not by the user.
     */
    public function confirmPayment(Payment $payment, ?string $providerPaymentId = null): Payment
    {
        return DB::transaction(function () use ($payment, $providerPaymentId) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== 'pending') {
                throw new BookingException("Payment is already {$payment->status}.");
            }

            $payment->update([
                'status' => 'paid',
                'provider_payment_id' => $providerPaymentId,
                'paid_at' => now(),
            ]);

            $payment->ticket->update(['status' => 'paid']);

            Log::info('Payment confirmed', ['payment_id' => $payment->id, 'ticket_id' => $payment->ticket_id]);

            return $payment;
        });
    }

    public function refund(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== 'paid') {
                throw new BookingException('Only paid payments can be refunded.');
            }

            $payment->update(['status' => 'refunded']);
            $payment->ticket->update(['status' => 'cancelled']);

            Log::warning('Payment refunded', ['payment_id' => $payment->id, 'ticket_id' => $payment->ticket_id]);

            return $payment;
        });
    }
}
