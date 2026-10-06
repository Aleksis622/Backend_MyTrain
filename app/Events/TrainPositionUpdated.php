<?php

namespace App\Events;

use App\Models\TrainPosition;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Frontend (Laravel Echo): Echo.channel('map-trains').listen('.TrainPositionUpdated', (position) => ...)
 * Note the leading dot, required because of broadcastAs().
 */
class TrainPositionUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(public TrainPosition $position)
    {
        $this->position->loadMissing('train');
    }

    public function broadcastOn(): Channel
    {
        return new Channel('map-trains');
    }

    public function broadcastAs(): string
    {
        return 'TrainPositionUpdated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->position->toArray();
    }
}
