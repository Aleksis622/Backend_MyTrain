<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrainPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'train_id',
        'latitude',
        'longitude',
        'speed',
        'heading',
        'reported_at',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'latitude'    => 'float',
        'longitude'   => 'float',
        'speed'       => 'float',
        'heading'     => 'float',
    ];

    public function train()
    {
        return $this->belongsTo(Train::class);
    }
}
