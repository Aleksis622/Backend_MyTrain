<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Route extends Model
{
    use HasFactory;

    protected $table = 'routes';

    protected $primaryKey = 'route_id';
    public $incrementing = false;

    protected $fillable = [
        'route_id',
        'route_short_name',
        'route_long_name',
        'route_desc',
        'route_type',
        'route_url',
        'route_color',
        'route_text_color',
        'agency_id',
    ];

    public $timestamps = false;

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id', 'agency_id');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class, 'route_id', 'route_id');
    }

    public function fareRules()
    {
        return $this->hasMany(FareRule::class, 'route_id', 'route_id');
    }

    
    public function journeys()
    {
        return $this->hasManyThrough(
            Journey::class,
            Trip::class,
            'route_id',   
            'trip_id',    
            'route_id',   
            'trip_id'     
        );
    }
}
