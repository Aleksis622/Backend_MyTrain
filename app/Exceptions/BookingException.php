<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * A ticket / payment rule was broken (already paid, no fare, train doesn't run that day...).
 * The message is safe to show to the user.
 */
class BookingException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json(['error' => $this->getMessage()], 422);
    }
}
