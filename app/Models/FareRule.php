<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FareRule extends Model
{
    protected $table = 'fare_rules';

    protected $fillable = [
        'fare_id',
        'route_id',
        'origin_id',
        'destination_id',
        'contains_id',
    ];

    public $timestamps = false;

    public function fare()
    {
        return $this->belongsTo(FareAttribute::class, 'fare_id', 'fare_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id', 'route_id');
    }

    public function origin()
    {
        return $this->belongsTo(Stop::class, 'origin_id', 'stop_id');
    }

    public function destination()
    {
        return $this->belongsTo(Stop::class, 'destination_id', 'stop_id');
    }

    public function contains()
    {
        return $this->belongsTo(Stop::class, 'contains_id', 'stop_id');
    }
}

