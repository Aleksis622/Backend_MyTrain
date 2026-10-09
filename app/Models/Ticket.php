<?php

namespace App\Models;

use App\Exceptions\BookingException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Ticket statuses and the only allowed changes:
 *
 *   pending ──paid──> paid ──refund──> refunded
 *      └──cancel / train left unpaid──> cancelled
 */
class Ticket extends Model
{
    use HasFactory;

    public const PENDING = 'pending';

    public const PAID = 'paid';

    public const CANCELLED = 'cancelled';

    public const REFUNDED = 'refunded';

    public const TRANSITIONS = [
        self::PENDING => [self::PAID, self::CANCELLED],
        self::PAID => [self::REFUNDED],
        self::CANCELLED => [],
        self::REFUNDED => [],
    ];

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
        'price' => 'decimal:2',
        'purchased_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function canBecome(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    /**
     * Change the status, refusing any change the status model does not allow.
     */
    public function moveTo(string $status): void
    {
        if (! $this->canBecome($status)) {
            throw new BookingException("A {$this->status} ticket cannot become {$status}.");
        }

        $this->update(['status' => $status]);
    }
}
