<?php

namespace App\Console\Commands;

use App\Models\Trip;
use App\Models\TripStatus;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Examples:
 *   php artisan trains:status 811 delayed --minutes=7 --reason="Signal fault"
 *   php artisan trains:status 811 cancelled --reason="Technical fault"
 *   php artisan trains:status 811 on_time
 */
#[Signature('trains:status
    {trip_id : GTFS trip id}
    {status : on_time, delayed or cancelled}
    {--minutes= : Delay in minutes (for "delayed")}
    {--reason= : Shown to passengers, e.g. "Signal fault"}
    {--date= : Service day YYYY-MM-DD (default today)}')]
#[Description('Mark a train as delayed or cancelled for one day, or back on time')]
class SetTripStatus extends Command
{
    public function handle(): int
    {
        $tripId = $this->argument('trip_id');
        $status = $this->argument('status');
        $date = Carbon::parse($this->option('date') ?? today())->startOfDay();

        if (! Trip::whereKey($tripId)->exists()) {
            $this->error("Trip {$tripId} does not exist.");

            return self::FAILURE;
        }

        if (! in_array($status, [TripStatus::ON_TIME, TripStatus::DELAYED, TripStatus::CANCELLED], true)) {
            $this->error('Status must be on_time, delayed or cancelled.');

            return self::FAILURE;
        }

        $minutes = (int) $this->option('minutes');
        if ($status === TripStatus::DELAYED && $minutes < 1) {
            $this->error('A delay needs --minutes=1 or more.');

            return self::FAILURE;
        }

        if ($status === TripStatus::ON_TIME) {
            TripStatus::where('trip_id', $tripId)->whereDate('service_date', $date)->delete();
        } else {
            TripStatus::updateOrCreate(
                ['trip_id' => $tripId, 'service_date' => $date->toDateString()],
                [
                    'status' => $status,
                    'delay_minutes' => $status === TripStatus::DELAYED ? $minutes : 0,
                    'reason' => $this->option('reason'),
                ],
            );
        }

        // The map caches train positions for a few seconds.
        Cache::forget('map.timetable-positions');

        $this->info("Trip {$tripId} on {$date->toDateString()}: {$status}".($status === TripStatus::DELAYED ? " (+{$minutes} min)" : ''));

        return self::SUCCESS;
    }
}
