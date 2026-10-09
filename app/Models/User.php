<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'language',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // is_admin is deliberately not fillable: only AdminUserSeeder sets it.
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Summary numbers for the profile and the admin user page.
     * A "trip" is a paid ticket, split by whether its train has already left.
     */
    public function tripStats(): array
    {
        $paidTickets = fn () => $this->tickets()->where('status', 'paid');

        return [
            'trips_taken' => $paidTickets()->whereHas('journey', fn ($journey) => $journey->where('departure_time', '<', now()))->count(),
            'upcoming_trips' => $paidTickets()->whereHas('journey', fn ($journey) => $journey->where('departure_time', '>=', now()))->count(),
            'total_spent' => (float) $this->payments()->where('status', 'paid')->sum('amount'),
            'last_purchase_at' => $this->tickets()->max('purchased_at'),
        ];
    }
}
