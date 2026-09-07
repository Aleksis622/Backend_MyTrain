<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    protected $table = 'agencies';

    protected $fillable = [
        'agency_id',
        'agency_name',
        'agency_url',
        'agency_timezone',
        'agency_lang',
        'agency_phone',
        'agency_fare_url',
        'agency_email',
    ];

    public $timestamps = false;

    public function routes()
    {
        return $this->hasMany(Route::class, 'agency_id', 'agency_id');
    }
}
