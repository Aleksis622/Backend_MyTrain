<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FareAttribute extends Model
{
    protected $table = 'fare_attributes';

    protected $fillable = [
        'fare_id',
        'price',
        'currency_type',
        'payment_method',
        'transfers',
        'agency_id',
        'transfer_duration',
    ];

    public $timestamps = false;

    public function fareRules()
    {
        return $this->hasMany(FareRule::class, 'fare_id', 'fare_id');
    }
}
