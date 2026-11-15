<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The seller business's payout bank account. The number is encrypted and shown masked. A reviewer
 * confirms that the account name matches the verified business; a change pauses payments for 48 hours.
 */
class PayoutAccount extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';

    protected $guarded = ['*'];

    protected $hidden = ['account_number', 'created_by', 'reviewed_by'];

    protected $casts = [
        'account_number' => 'encrypted',
        'is_change' => 'boolean',
        'active_from' => 'datetime',
        'reviewed_at' => 'datetime',
        'called_on_file_at' => 'datetime',
        'replaced_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeCurrent($q)
    {
        return $q->whereNull('replaced_at')->where('status', '!=', self::STATUS_REJECTED);
    }
}
