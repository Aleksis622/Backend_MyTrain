<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Train extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'number',
        'type',
        'operator',
    ];

    public function journeys()
    {
        return $this->hasMany(Journey::class);
    }

    public function positions()
    {
        return $this->hasMany(TrainPosition::class);
    }
}
