<?php

namespace App\Events;

use App\Models\TrainPosition;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class TrainPositionUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public $position;

    public function __construct(TrainPosition $position)
    {
        $this->position = $position->load('train');
    }

    public function broadcastOn()
    {
        return new Channel('map-trains');
    }

    public function broadcastWith()
    {
        return $this->position->toArray();
    }
}
