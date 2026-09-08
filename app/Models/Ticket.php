<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'journey_id',
        'ticket_code',
        'price',
        'currency',
        'status',
        'purchased_at',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function journey()
    {
        return $this->belongsTo(Journey::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
