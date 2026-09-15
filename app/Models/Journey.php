<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Journey extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'train_id',
        'from_stop_id',
        'to_stop_id',
        'departure_time',
        'arrival_time',
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'arrival_time'   => 'datetime',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'trip_id');
    }

    public function train()
    {
        return $this->belongsTo(Train::class);
    }

    public function fromStop()
    {
        return $this->belongsTo(Stop::class, 'from_stop_id');
    }

    public function toStop()
    {
        return $this->belongsTo(Stop::class, 'to_stop_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
