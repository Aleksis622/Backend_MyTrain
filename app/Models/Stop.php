<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stop extends Model
{
    use HasFactory;

    protected $table = 'stops';

    protected $primaryKey = 'stop_id';
    public $incrementing = false;

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

    
    public function originJourneys()
    {
        return $this->hasMany(Journey::class, 'from_stop_id');
    }

    
    public function destinationJourneys()
    {
        return $this->hasMany(Journey::class, 'to_stop_id');
    }
}

