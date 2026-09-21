<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;

class PaymentService
{
   
    public function createPendingPayment(Ticket $ticket, $userId, $provider)
    {
        // Prevent duplicate payments
        if ($ticket->payment && $ticket->payment->status === 'paid') {
            throw new \Exception("Ticket already paid");
        }

        return Payment::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $userId,
            'provider'  => $provider,
            'amount'    => $ticket->price,
            'currency'  => $ticket->currency,
            'status'    => 'pending',
        ]);
    }

   
    public function confirmPayment(Payment $payment, $providerPaymentId = null)
    {
        $payment->update([
            'status' => 'paid',
            'provider_payment_id' => $providerPaymentId,
            'paid_at' => now(),
        ]);

        // Mark ticket as paid
        $payment->ticket->update([
            'status' => 'paid',
        ]);

        Log::info("Payment confirmed", [
            'payment_id' => $payment->id,
            'ticket_id' => $payment->ticket_id,
        ]);

        return $payment;
    }

    
    public function refund(Payment $payment)
    {
        if ($payment->status !== 'paid') {
            throw new \Exception("Cannot refund unpaid payment");
        }

        $payment->update([
            'status' => 'refunded',
        ]);

        Log::warning("Payment refunded", [
            'payment_id' => $payment->id,
            'ticket_id' => $payment->ticket_id,
        ]);

        return $payment;
    }
}
