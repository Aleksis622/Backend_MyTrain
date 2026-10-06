<?php

namespace App\Console\Commands;

use App\Events\TrainPositionUpdated;
use App\Models\StopTime;
use App\Models\Train;
use App\Models\TrainPosition;
use App\Models\Trip;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Sleep;

/**
 * Moves a train along a GTFS trip's stops and broadcasts every position,
 * so the live map can be tested without a real GPS device.
 *
 * Example: php artisan trains:simulate 811 --train=1 --interval=1
 */
#[Signature('trains:simulate
    {trip_id? : GTFS trip to follow (random trip if omitted)}
    {--train= : trains.id to move (first train if omitted)}
    {--interval=2 : Seconds between position updates}
    {--steps=10 : Positions sent between two stops}')]
#[Description('Simulate a train driving along a trip and broadcast its positions to the map')]
class SimulateTrain extends Command
{
    public function handle(): int
    {
        $train = $this->option('train') ? Train::find($this->option('train')) : Train::first();

        if (! $train) {
            $this->error('No train found. Run: php artisan db:seed --class=TrainSeeder');

            return self::FAILURE;
        }

        $tripId = $this->argument('trip_id') ?? Trip::inRandomOrder()->value('trip_id');

        $stops = StopTime::with('stop')
            ->where('trip_id', $tripId)
            ->orderBy('stop_sequence')
            ->get()
            ->pluck('stop')
            ->filter()
            ->values();

        if ($stops->count() < 2) {
            $this->error("Trip {$tripId} has fewer than two stops.");

            return self::FAILURE;
        }

        $steps = max(1, (int) $this->option('steps'));
        $interval = max(0, (float) $this->option('interval'));

        $this->info("Train #{$train->id} ({$train->name}) following trip {$tripId} through {$stops->count()} stops. Ctrl+C to stop.");

        foreach ($stops->sliding(2) as $pair) {
            [$from, $to] = $pair->values()->all();
            $heading = $this->bearing($from->stop_lat, $from->stop_lon, $to->stop_lat, $to->stop_lon);

            $this->line("  {$from->stop_name} → {$to->stop_name}");

            for ($step = 0; $step < $steps; $step++) {
                $progress = $step / $steps;

                $this->report($train, [
                    'latitude' => $from->stop_lat + ($to->stop_lat - $from->stop_lat) * $progress,
                    'longitude' => $from->stop_lon + ($to->stop_lon - $from->stop_lon) * $progress,
                    'speed' => $step === 0 ? 0 : 80,
                    'heading' => $heading,
                ]);

                Sleep::for($interval)->seconds();
            }
        }

        $last = $stops->last();
        $this->report($train, ['latitude' => $last->stop_lat, 'longitude' => $last->stop_lon, 'speed' => 0, 'heading' => null]);
        $this->info("Arrived at {$last->stop_name}.");

        return self::SUCCESS;
    }

    /**
     * @param  array{latitude: float|string, longitude: float|string, speed: int, heading: ?float}  $position
     */
    private function report(Train $train, array $position): void
    {
        $position = TrainPosition::create([
            'train_id' => $train->id,
            'latitude' => (float) $position['latitude'],
            'longitude' => (float) $position['longitude'],
            'speed' => $position['speed'],
            'heading' => $position['heading'],
            'reported_at' => now(),
        ]);

        event(new TrainPositionUpdated($position));
    }

    /**
     * Compass direction (0-360°) from point A to point B.
     */
    private function bearing(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        [$lat1, $lon1, $lat2, $lon2] = array_map('deg2rad', [$lat1, $lon1, $lat2, $lon2]);

        $y = sin($lon2 - $lon1) * cos($lat2);
        $x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($lon2 - $lon1);

        return fmod(rad2deg(atan2($y, $x)) + 360, 360);
    }
}
