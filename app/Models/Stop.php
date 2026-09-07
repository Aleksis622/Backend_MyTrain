<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stop extends Model
{
    protected $table = 'stops';

    protected $fillable = [
        'stop_id',
        'stop_code',
        'stop_name',
        'stop_desc',
        'stop_lat',
        'stop_lon',
        'zone_id',
        'stop_url',
        'location_type',
        'parent_station',
    ];

    public $timestamps = false;

    public function stopTimes()
    {
        return $this->hasMany(StopTime::class, 'stop_id', 'stop_id');
    }
}
