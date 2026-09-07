<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StopTime extends Model
{
    protected $table = 'stop_times';

    protected $fillable = [
        'trip_id',
        'arrival_time',
        'departure_time',
        'stop_id',
        'stop_sequence',
        'pickup_type',
        'drop_off_type',
    ];

    public $timestamps = false;

    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'trip_id');
    }

    public function stop()
    {
        return $this->belongsTo(Stop::class, 'stop_id', 'stop_id');
    }
}
