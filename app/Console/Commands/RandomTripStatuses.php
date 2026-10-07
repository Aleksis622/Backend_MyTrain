<?php

namespace App\Console\Commands;

use App\Models\TripStatus;
use App\Services\TrainSearchService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Demo data: there is no live feed from the railway, so this makes a few of the
 * day's trains late and cancels some, to see how the app shows disruptions.
 *
 * Example: php artisan trains:random-status --delayed=8 --cancelled=2 --fresh
 */
#[Signature('trains:random-status
    {--delayed=5 : How many trains get a delay}
    {--cancelled=1 : How many trains get cancelled}
    {--date= : Service day YYYY-MM-DD (default today)}
    {--fresh : Remove that day\'s existing statuses first}')]
#[Description('Give random trains of a day a delay or a cancellation (demo data)')]
class RandomTripStatuses extends Command
{
    private const DELAY_REASONS = [
        'Signal fault',
        'Late arrival of the previous train',
        'Technical inspection',
        'Weather conditions',
        'Track works',
    ];

    private const CANCEL_REASONS = [
        'Technical fault',
        'Staff shortage',
        'Track works',
    ];

    public function handle(TrainSearchService $trains): int
    {
        $date = Carbon::parse($this->option('date') ?? today())->startOfDay();

        if ($this->option('fresh')) {
            TripStatus::whereDate('service_date', $date)->delete();
        }

        // Trips of that day that have not finished yet (all of them for a future day).
        $notFinishedAfter = $date->isToday() ? now()->format('H:i:s') : '00:00:00';
        $tripIds = DB::table('stop_times')
            ->join('trips', 'trips.trip_id', '=', 'stop_times.trip_id')
            ->whereIn('trips.service_id', $trains->activeServiceIds($date))
            ->whereNotIn('trips.trip_id', TripStatus::whereDate('service_date', $date)->select('trip_id'))
            ->groupBy('stop_times.trip_id')
            ->havingRaw('MAX(stop_times.arrival_time) >= ?', [$notFinishedAfter])
            ->pluck('stop_times.trip_id')
            ->shuffle();

        $delayed = $tripIds->splice(0, (int) $this->option('delayed'));
        $cancelled = $tripIds->splice(0, (int) $this->option('cancelled'));

        foreach ($delayed as $tripId) {
            $this->save($tripId, $date, TripStatus::DELAYED, random_int(2, 25), Arr::random(self::DELAY_REASONS));
        }
        foreach ($cancelled as $tripId) {
            $this->save($tripId, $date, TripStatus::CANCELLED, 0, Arr::random(self::CANCEL_REASONS));
        }

        Cache::forget('map.timetable-positions');

        $this->info("{$date->toDateString()}: {$delayed->count()} delayed, {$cancelled->count()} cancelled.");
        $this->line('Delayed: '.$delayed->implode(', '));
        $this->line('Cancelled: '.$cancelled->implode(', '));

        return self::SUCCESS;
    }

    private function save(string $tripId, Carbon $date, string $status, int $minutes, string $reason): void
    {
        TripStatus::create([
            'trip_id' => $tripId,
            'service_date' => $date->toDateString(),
            'status' => $status,
            'delay_minutes' => $minutes,
            'reason' => $reason,
        ]);
    }
}
