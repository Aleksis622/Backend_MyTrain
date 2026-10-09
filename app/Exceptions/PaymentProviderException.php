<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Stripe could not be reached or refused a request. The real reason is logged;
 * the user gets a general "try again" message.
 */
class PaymentProviderException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json(['error' => 'The payment provider is not available right now. Please try again.'], 502);
    }
}
