<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CalendarDate extends Model
{
    use HasFactory;

    protected $table = 'calendar_dates';

    protected $fillable = [
        'service_id',
        'date',
        'exception_type',
    ];

    public $timestamps = false;

    public function calendar()
    {
        return $this->belongsTo(Calendar::class, 'service_id', 'service_id');
    }
}
