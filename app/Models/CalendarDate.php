<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarDate extends Model
{
    protected $table = 'calendar_dates';

    protected $fillable = [
        'service_id',
        'date',
        'exception_type',
    ];

    public $timestamps = false;

    public function trip()
    {
        return $this->belongsTo(Trip::class, 'service_id', 'service_id');
    }
}
