<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrainTrackerToken
{
    /**
     * Only allow trusted GPS devices / simulators (Authorization: Bearer <TRAIN_TRACKER_TOKEN>) to report positions.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.train_tracker.token');

        if (! $expected || ! hash_equals($expected, (string) $request->bearerToken())) {
            return response()->json(['error' => 'Invalid tracker token'], 401);
        }

        return $next($request);
    }
}
