<?php

namespace App\Models;

use App\Exceptions\BookingException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Payment statuses and the only allowed changes:
 *
 *   pending ──Stripe confirms──> paid ──refund──> refunded
 *      ├──user leaves checkout / ticket cancelled──> cancelled
 *      └──checkout expired / provider error──> failed
 *
 * A payment only becomes "paid" from the payment provider (webhook or a server-side
 * check with Stripe), never because the user says so. A late Stripe confirmation can
 * still turn a cancelled/failed payment into paid (the money really arrived), or straight
 * into refunded when its ticket can no longer be paid (it was paid or cancelled meanwhile).
 */
class Payment extends Model
{
    use HasFactory;

    public const PENDING = 'pending';

    public const PAID = 'paid';

    public const FAILED = 'failed';

    public const CANCELLED = 'cancelled';

    public const REFUNDED = 'refunded';

    public const TRANSITIONS = [
        self::PENDING => [self::PAID, self::FAILED, self::CANCELLED, self::REFUNDED],
        self::FAILED => [self::PAID, self::REFUNDED],
        self::CANCELLED => [self::PAID, self::REFUNDED],
        self::PAID => [self::REFUNDED],
        self::REFUNDED => [],
    ];

    public const STRIPE = 'stripe';

    // Paid at the ticket office, marked by an admin.
    public const MANUAL = 'manual';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'provider',
        'provider_payment_id',
        'provider_session_id',
        'checkout_url',
        'checkout_expires_at',
        'amount',
        'currency',
        'status',
        'paid_at',
        'provider_refund_id',
        'refunded_at',
    ];

    protected $hidden = [
        'active_ticket_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'checkout_expires_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function canBecome(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function moveTo(string $status, array $attributes = []): void
    {
        if (! $this->canBecome($status)) {
            throw new BookingException("A {$this->status} payment cannot become {$status}.");
        }

        $this->update(['status' => $status, ...$attributes]);
    }

    /**
     * A pending Stripe payment whose checkout page can still be used.
     */
    public function hasOpenCheckout(): bool
    {
        return $this->status === self::PENDING
            && $this->provider === self::STRIPE
            && $this->checkout_url
            && $this->checkout_expires_at?->isAfter(now()->addMinute());
    }
}
