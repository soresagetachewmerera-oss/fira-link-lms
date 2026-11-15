<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A chat between two businesses: one per deal room, or one "Message seller" thread per pair. */
class TradeConversation extends Model
{
    protected $guarded = ['*'];

    protected $casts = ['last_message_at' => 'datetime'];

    public function buyerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'buyer_organization_id');
    }

    public function sellerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'seller_organization_id');
    }

    public function dealRoom(): BelongsTo
    {
        return $this->belongsTo(DealRoom::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(CoffeeListing::class, 'listing_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TradeMessage::class);
    }

    public function involves(?int $orgId): bool
    {
        return $orgId && in_array($orgId, [(int) $this->buyer_organization_id, (int) $this->seller_organization_id], true);
    }

    public function otherOrganizationId(int $orgId): int
    {
        return (int) ($orgId === (int) $this->buyer_organization_id ? $this->seller_organization_id : $this->buyer_organization_id);
    }
}
