<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    protected $table = 'agencies';

    protected $primaryKey = 'agency_id';

    public $incrementing = false;

    protected $keyType = 'string';

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
