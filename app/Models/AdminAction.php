<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry of the admin activity log, e.g. "Admin refunded ticket MT-1234".
 * The frontend turns "action" + "details" into a translated sentence.
 */
class AdminAction extends Model
{
    public const TICKET_CANCELLED = 'ticket_cancelled';

    public const TICKET_MARKED_PAID = 'ticket_marked_paid';

    public const TICKET_REFUNDED = 'ticket_refunded';

    public const USER_UPDATED = 'user_updated';

    public const USER_DELETED = 'user_deleted';

    public const TRAIN_STATUS_CHANGED = 'train_status_changed';

    public const ACCOUNT_UPDATED = 'account_updated';

    public const PASSWORD_CHANGED = 'password_changed';

    public const SUBJECT_TYPES = ['ticket', 'user', 'train', 'account'];

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * AdminAction::record($request->user(), AdminAction::TICKET_REFUNDED, 'ticket', $ticket->id, ['ticket_code' => ...])
     */
    public static function record(User $admin, string $action, string $subjectType, string|int|null $subjectId, array $details = []): self
    {
        return static::create([
            'user_id' => $admin->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId === null ? null : (string) $subjectId,
            'details' => $details ?: null,
        ]);
    }
}
