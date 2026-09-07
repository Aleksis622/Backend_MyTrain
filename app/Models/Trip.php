<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $table = 'trips';

    protected $fillable = [
        'route_id',
        'service_id',
        'trip_id',
        'trip_headsign',
        'shape_id',
    ];

    public $timestamps = false;

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id', 'route_id');
    }

    public function stopTimes()
    {
        return $this->hasMany(StopTime::class, 'trip_id', 'trip_id');
    }

    public function calendar()
    {
        return $this->belongsTo(Calendar::class, 'service_id', 'service_id');
    }

    public function calendarDates()
    {
        return $this->hasMany(CalendarDate::class, 'service_id', 'service_id');
    }
}
