<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Calendar extends Model
{
    use HasFactory;

    protected $table = 'calendar';

    protected $primaryKey = 'service_id';
    public $incrementing = false;

    protected $fillable = [
        'service_id',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
        'start_date',
        'end_date',
    ];

    public $timestamps = false;

    public function trips()
    {
        return $this->hasMany(Trip::class, 'service_id', 'service_id');
    }

    public function calendarDates()
    {
        return $this->hasMany(CalendarDate::class, 'service_id', 'service_id');
    }
}
