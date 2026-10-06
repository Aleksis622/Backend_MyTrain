<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Users may only see, confirm, or refund their own payments.
     */
    public function manage(User $user, Payment $payment): bool
    {
        return $user->id === $payment->user_id;
    }
}
