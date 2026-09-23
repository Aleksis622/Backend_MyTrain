<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'trip_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'trip_id',
        'route_id',
        'service_id',
        'trip_headsign',
        'direction_id',
    ];

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id', 'route_id');
    }

    public function calendar()
    {
        return $this->belongsTo(Calendar::class, 'service_id', 'service_id');
    }

    public function calendarDates()
    {
        return $this->hasMany(CalendarDate::class, 'service_id', 'service_id');
    }

    public function stopTimes()
    {
        return $this->hasMany(StopTime::class, 'trip_id', 'trip_id');
    }
}
