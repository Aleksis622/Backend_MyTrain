<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * A delay or cancellation of one trip on one day. Trips without a row run on time.
 */
class TripStatus extends Model
{
    public const ON_TIME = 'on_time';

    public const DELAYED = 'delayed';

    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'trip_id',
        'service_date',
        'status',
        'delay_minutes',
        'reason',
    ];

    // service_date stays a plain "YYYY-MM-DD" string so it is stored the same way on every database.
    protected $casts = [
        'delay_minutes' => 'integer',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'trip_id');
    }

    /**
     * Statuses of all disrupted trips on $date, keyed by trip_id.
     *
     * @return Collection<string, TripStatus>
     */
    public static function forDate(CarbonInterface $date): Collection
    {
        return static::whereDate('service_date', $date)->get()->keyBy('trip_id');
    }
}
