<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One business blocked another: no messages, quotes, samples or new deal rooms between them. */
class BusinessBlock extends Model
{
    protected $guarded = ['*'];

    public function blockedOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'blocked_organization_id');
    }

    /** Either side blocked the other. */
    public static function between(?int $a, ?int $b): bool
    {
        if (!$a || !$b) {
            return false;
        }

        return self::where(fn ($q) => $q->where('organization_id', $a)->where('blocked_organization_id', $b))
            ->orWhere(fn ($q) => $q->where('organization_id', $b)->where('blocked_organization_id', $a))
            ->exists();
    }
}
